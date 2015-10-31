# NASA Astronomy Picture of the Day - API Wrapper

This package is used by [AstroSplash](http://astrosplash.com) to retrieve images from the NASA Astronomy Picture of the Day API.

Check the [AstroSplash repository](https://github.com/AndrewCarterUK/AstroSplash) to see this API wrapper in use!

Check the [NASA API website](https://api.nasa.gov/api.html#apod) to see how to use their API directly.

## Description

On its own, the NASA API does not provide thumbnails or return more than one result per request.

This wrapper provides a thin layer above the NASA API which includes these features. It does this by maintaining a store of thumbnails and JSON files in a public web root.

The methods that update, query and clear the store are documented with annotations in the `API.php` and `APIInterface.php` files in the `src` directory.
