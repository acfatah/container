dev-master (2015-06-15)
-----------------------
* Initial development

dev-master (2015-12-17)
-----------------------
* Moved exception to "Exception" subdirectory
* Added "ContainerException" class
* "NotFoundException" extends "ContainerException"

dev-master [BC Breaks] (2015-12-16)
-----------------------------------
* No longer need to wrap new and singleton with their respective class and
  replaced with **setNew** and **single** method
* New array configuration structure
* Added **setFromArray** method
* Added **setMaxDepth** method
* Removed Serializable class and container no longer serializable
* Changed some variable naming
* Automatic class resolution
