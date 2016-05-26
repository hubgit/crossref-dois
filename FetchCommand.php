<?php

use GuzzleHttp\Client;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 *
 */
class FetchCommand extends Command
{
    public function configure()
    {
        $this->setName('crossref:fetch')
            ->addArgument('output')
            ->setDescription('Fetch all journal article metadata from Crossref');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $outputFile = $input->getArgument('output');
        $outputHandle = fopen($outputFile, 'w');

        $client = new Client([
          //'debug' => true
        ]);

        $headers = [
            'Accept' => 'application/json',
            'Accept-Encoding' => 'gzip,deflate',
            'User-Agent' => 'crossref-dois/0.1 (+https://github.com/hubgit/crossref-dois/)',
        ];

        // https://api.crossref.org/works?filter=type:journal-article&cursor=*

        $filters = [
            'type' => 'journal-article'
        ];

        $filter = implode(',', array_map(function ($key) use ($filters) {
            return $key . ':' . $filters[$key];
        }, array_keys($filters)));

        $params = [
            'filter' => $filter,
            'cursor' => '*',
            'rows' => 1000,
        ];

        $progress = new ProgressBar($output);
        $progressStarted = false;

        do {
            $response = $client->get('https://api.crossref.org/works', [
                //'debug' => true,
                'connect_timeout' => 10,
                'timeout' => 120,
                'query' => $params,
                'headers' => $headers,
            ]);

            $data = json_decode($response->getBody(), true);

            if (!$progressStarted) {
              $progress->start($data['message']['total-results']);
              $progressStarted = true;
            }

            $items = $this->parseResponse($data);

            foreach ($items as $item) {
                fwrite($outputHandle, json_encode($item) . "\n");
            }

            $params['cursor'] = $data['message']['next-cursor'];

            $progress->advance(count($items));
        } while ($params['cursor']);

        $progress->finish();
    }

    /**
     * @param array $data
     *
     * @throws Exception
     *
     * @return array
     */
    private function parseResponse(array $data)
    {
        if (!$data) {
            throw new \Exception('No data');
        }

        if ($data['status'] !== 'ok') {
            throw new \Exception('Not ok');
        }

        return $data['message']['items'];
    }
}
