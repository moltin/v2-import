# Moltin v2 Import

* [Website](https://moltin.com)
* [License](https://github.com/moltin/v2-import/master/LICENSE)
* Status: In Development

The moltin v2 import is a set of tools to generate example store data and import it into a new moltin v2 store. It's something we quickly put together for testing and building before you have real data.

We have included a few pre build data sets which are pulled from Amazon's bestseller lists in both the UK and US. There's also a tool to generate more data from additional categories or regional Amazon sites if required.

> This tool is still new and relatively untested. You may run into some issues during import. Please report them and we will continue to update as we progress with v2.

## Requirements
* PHP 5.4+
* PHP working in terminal/cmd
* A moltin v2 store and your public/private keys to hand

> If you're on Windows using cmd, it is highly recommended to install [Ansicon](https://github.com/adoxa/ansicon), without it you will experience odd output and no colours.

## Installation
Simply clone down the repo and that's it, we include a basic SDK and other script requirements in the package.

## Usage
To start, open up a terminal window and cd into the directory you just cloned.

### Importing an Existing Data Set
* Type `php import.php`
* You should see is a warning that existing data will be purged, type `y` and hit 'enter'
* Next you'll pick a data set from the `data/` directory, type the corresponding number and hit 'enter'
* Finally enter your public and then private keys for your store

The import process will run through deleting any `files`, `categories`, `brands` or `products` you already have. Then moving on to import of the new data from your data set.

> This tool can be used to import products from any source, your JSON data simply has to match the expected format for this script and the API.

### Building Additional Data Sets
* Type `php scrape.php`
* You will be asked to choose the Amazon store you wish to use
* Next you'll be asked the category to get data from, enter your selection and hit enter
* Finally you'll be asked how many items you'd like, maximum is 100.

The script will then run through Amazon's best sellers for your selected region and category before adding the data to the `data/` directory.

> At any time Amazon can, and will, update their listings and page layout. The tool tries to get around these changes as best it can but it is liable to stop working at any time. If you run into issues please report them.

## Contributing
Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits
- [Moltin](https://github.com/moltin)
- [All Contributors](https://github.com/moltin/v2-import/contributors)

## License
Please see [License File](LICENSE) for more information.