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
        $outputDir = $input->getArgument('output');

        if (!file_exists($outputDir)) {
            mkdir($outputDir, 0775, true);
        }

        $this->fetch($outputDir,$output);
    }

    /**
     * @param string          $outputDir
     * @param OutputInterface $output
     *
     * @throws Exception
     */
    protected function fetch($outputDir, OutputInterface $output)
    {
        $client = new Client([
          //'debug' => true
        ]);

        $headers = [
            'Accept' => 'application/json',
            'Accept-Encoding' => 'gzip,deflate',
            'User-Agent' => 'crossref-dois/0.1 (+https://github.com/hubgit/crossref-dois/)',
        ];

        // start from 2 days ago, to be sure it's complete
        $current = new \DateTime('-2 DAYS');

        // https://api.crossref.org/works?filter=type:journal-article&sort=deposited&order=asc&rows=1
        $earliest = new \DateTime('2007-02-13');

        while ($current >= $earliest) {
            $date = $current->format('Y-m-d');

            $outputFile = $outputDir . '/' . $date . '.json';

            if (file_exists($outputFile) && filesize($outputFile)) {
              break;
            }

            $outputHandle = fopen($outputFile, 'w');

            $filters = [
                'type' => 'journal-article',
                'from-deposit-date' => $date,
                'until-deposit-date' => $date,
            ];

            $filter = implode(',', array_map(function ($key) use ($filters) {
                return $key . ':' . $filters[$key];
            }, array_keys($filters)));

            $params = [
                'filter' => $filter,
                'rows' => 1000,
                'offset' => 0,
            ];

            $output->writeLn('');
            $output->writeln(sprintf('Fetching journal article DOIs deposited on %s', $date));

            do {
                $response = $client->get('https://api.crossref.org/works', [
                    //'debug' => true,
                    'connect_timeout' => 10,
                    'timeout' => 120,
                    'query' => $params,
                    'headers' => $headers,
                ]);

                $data = json_decode($response->getBody(), true);

                $total = $data['message']['total-results'];

                $items = $this->parseResponse($data);

                if ($params['offset'] === 0) {
                    $progress = new ProgressBar($output, $total);
                    $progress->start();
                }

                foreach ($items as $item) {
                    fwrite($outputHandle, json_encode($item) . "\n");
                }

                $params['offset'] += $params['rows'];

                $progress->setProgress($params['offset']);

            } while ($params['offset'] <= $total);

            $progress->finish();

            $current->modify('-1 DAY');
        }
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
