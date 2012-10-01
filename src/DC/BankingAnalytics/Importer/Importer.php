<?php

namespace DC\BankingAnalytics\Importer;

abstract class Importer {

    abstract protected function parseTransaction($row);

    public function process($file) {



        if(!file_exists($file)) {
            throw new \Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException($file);
        }

        ini_set('auto_detect_line_endings', true);

        $handle = @fopen($file, 'r');
        if ($handle) {
            while (($buffer = fgets($handle, 4096)) !== false) {
                $this->parseTransaction($buffer);
            }

            fclose($handle);
        }
    }
}