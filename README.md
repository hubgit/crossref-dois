## Requirements

* PHP
* Composer

## Install requirements

`composer install`

## Usage

`php index.php crossref:fetch path/to/output/directory`

e.g. `php index.php crossref:fetch data/doi`

The output is a newline-delimited JSON file for each day, with each item on a separate line.
