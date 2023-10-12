<?php

declare(strict_types=1);

namespace Keboola\OpenLineageGenerator;

use DateTimeImmutable;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;
use Throwable;

class OpenLineageWriter
{
    public function __construct(
        private Client $queueClient,
        private Client $openLineageClient,
        private LoggerInterface $logger,
        private DateTimeImmutable $createdTimeFrom,
        private string $openLineageEndpoint,
        private bool $jobNameAsConfig = false,
        private bool $includeEventSchemaUrl = false,
    ) {
    }

    public function write(): void
    {
        $jobsResponse = $this->getJobs();

        foreach (array_reverse($jobsResponse) as $job) {
            if (!isset($job['result']['input'])) {
                $this->logger->info(
                    sprintf('Skipping older job "%s" without I/O in the result.', $job['id']),
                );
                continue;
            }
            $this->logger->info(sprintf('Job %s import to OpenLineage API - start', $job['id']));

            $lineageResponse = $this->getJobLineage($job['id']);
            foreach ($lineageResponse as $event) {
                if ($this->includeEventSchemaUrl) {
                    $event['schemaURL'] = 'https://openlineage.io/spec/1-0-2/OpenLineage.json#/$defs/RunEvent';
                }
                if ($this->jobNameAsConfig) {
                    $event['job']['name'] = sprintf('%s-%s', $job['component'], $job['config']);
                }
                $this->logger->info(sprintf('- Sending %s event', $event['eventType']));

                try {
                    $this->openLineageClient->request('POST', $this->openLineageEndpoint, [
                        'body' => json_encode($event),
                    ]);
                } catch (RequestException $e) {
                    if (str_contains($e->getMessage(), 'cURL error 3:')) {
                        throw new GeneratorException('Malformed URL of OpenLineage server');
                    }
                    throw new GeneratorException($e->getMessage());
                } catch (Throwable $e) {
                    throw new GeneratorException($e->getMessage());
                }
            }

            $this->logger->info(sprintf('Job %s import to OpenLineage API - end', $job['id']));
        }

        $this->logger->info('Done');
    }

    private function getJobs(): array
    {
        $response = $this->queueClient->request(
            'GET',
            sprintf(
                '/jobs?%s',
                http_build_query([
                    'createdTimeFrom' => $this->createdTimeFrom->format('c'),
                    'sortOrder' => 'desc',
                    'status' => 'success',
                ]),
            ),
        );
        return $this->decodeResponse($response);
    }

    private function getJobLineage(string $jobId): array
    {
        $response = $this->queueClient->request(
            'GET',
            sprintf('/jobs/%s/open-api-lineage', $jobId),
        );

        return $this->decodeResponse($response);
    }

    private function decodeResponse(ResponseInterface $response): array
    {
        return (array) json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
    }
}
