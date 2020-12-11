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

### src/conf
Specific configuration for module  


### src/customfields
Custom fields are extensions which add a field to various objects in Phabricator.
- Lotus Link : Field to link task with object in "IBM Lotus Notes" 
- Total estimate point : Display the total of estimate point of child task (can only be work with our specific version of phabricator)
- Total point used : Display the total of used point of child task (can only be work with our specific version of phabricator)
  A custom field must be created with name match:points_consomme
```
"match:points_consomme": {
    "name": "Charge consomm\u00e9e (jour)",
    "type": "int",
    "required": false,
    "edit": true,
    "view": false,
    "taskcard.enable": true,
    "taskcard.type": "shade",
    "taskcard.color": "yellow",
    "taskcard.class": "phui-workcard-points",
    "taskheader.enable": "true",
    "taskheader.type": "shade",
    "taskheader.color": "yellow",
    "taskheader.class": "phui-workcard-points"
  }
```
- Remaining point : Display the remaining point

### src/future
- MatchPhutilHTTPEngineExtension : Engine to activate company proxy
    - HTTP_PROX, HTTPS_PROXY, NO_PROXY have to be define in configuration 

### src/infrastructure
- Daemon
    - PhabricatorUserManagementDaemon : Create user from LDAP adptater to avoid waiting first connexion
    To start daemon ``./bin/phd launch PhabricatorUserManagementDaemon`` 

### src/markup
Custom remarkup engin 
- Kroki (Remarkup extension author : zengxs): see dociumentation on https://kroki.io/#how 

### src/translations
Translation for new text  

### src/script
#### UpdatePolicies
- Update diffusion : Update policies and authorisation with Match rules :
    - Policies : 
        - Visible To <space> All Users
        - Editable By Administrators
        - Pushable By Custom Policy [Administrator && Team with name 'Equipe <spaceName>']
    - Authorisation :
        - Allow enormous change : false
        - Allow dangerous change : true 
```
$ ./bin/update_policies diffusion --force <true|false> --update-authorization <true|false>
```

- Manage Bot user:
    - Create user if not exist
    - Attach to the defined groupe (optional)
    - Generate SSH key pair and download private key, if directory option is defined
```
server --name-format Bot-Serveur-%s --group-name "Group-name" --robot-names "toto, tata, titi, ..." --ssh-directory "myDirectoryPath"
```


# Contributing
Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.

Please make sure to update tests as appropriate.

# Support
Supermarches Match does not provide support for this extension and cannot be held responsible for the use of this extension