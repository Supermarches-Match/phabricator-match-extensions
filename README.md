# About this repository
This repository contains miscellaneous extensions to Phabricator which are specialized for the needs of Supermarches Match's Phabricator instance

These extensions provide some basic custom functionality.

# Installation
This Repository consists of a single librairie module which can be used in 
phabricator by simply adding the repository root to the list of library paths
specified by the key `load-libraries` within phabricator's config.

### For example:

```json
"load-libraries": [
  "/path/to/this/repository/",
  "/path/to/another/extension/"
]
```

For more details, see [this article](https://secure.phabricator.com/book/phabcontrib/article/adding_new_classes/#linking-with-phabricator) in the phabricator documentation.

##Overview of extensions
The extensions are under the `src/` directory, organized into sub-directories
by extension type.

### src/customfields
Custom fields are extensions which add a field to various objects in Phabricator.
- Lotus Link : Field to link task with object in "IBM Lotus Notes"  

### src/translations
Translation for new text  


# Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

# Support
Supermarches Match does not provide support for this extension and cannot be held responsible for the use of this extension