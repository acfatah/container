Change Log
==========

[dev-master] (BC Breaks) - 2015-12-16
-----------------------------------

### Changed
- Container is no longer serializable
- No longer need to wrap new and singleton with their respective class and
  replaced with **setNew** and **single** method
- New array configuration structure
- Changed some variable naming and terms

### Added
- Can add resolver from a configuration array using **setFromArray** method
- Automatic class resolution
- Can limit maximum recursion for automatic resolution using **setMaxDepth**
  method

### Removed
- Removed `Acfatah\Container\NewInstance` class
- Removed `Acfatah\Container\Singleton` class
- Removed `Acfatah\Container\SerializableClosure` class

[dev-master] - 2015-12-17
-----------------------

### Changed
- Moved `Acfatah\Container\NotFoundException` to
  `Acfatah\Container\Exception\NotFoundException`
- `Acfatah\Container\Exception\NotFoundException` extends
  `Acfatah\Container\Exception\ContainerException`

### Added
* Added `Acfatah\Container\Exception\ContainerException` class

[dev-master] - 2015-06-15
-----------------------

### Added
- Initial development
