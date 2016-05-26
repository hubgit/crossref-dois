## Requirements

* PHP
* Composer

## Install requirements

`composer install`

## Usage

`php index.php crossref:fetch /path/to/output/file`

e.g. `php index.php crossref:fetch crossref-journal-articles.ndjson`

The output is a newline-delimited JSON file, with each item on a separate line.
