# parallel-processes

[![Latest Version on Packagist][ico-version]][link-packagist]
[![Software License][ico-license]](LICENSE.md)
[![Build Status][ico-travis]][link-travis]
[![Coverage Status][ico-scrutinizer]][link-scrutinizer]
[![Quality Score][ico-code-quality]][link-code-quality]
[![Total Downloads][ico-downloads]][link-downloads]


## Installation

The preferred method of installation is via [Composer](http://getcomposer.org/). Run the following command to install the latest version of a package and add it to your project's `composer.json`:

```bash
composer require idimsh/parallel-processes
```
## Purpose
To be able to run multiple shell commands in parallel, leveraging [Symfony Process](https://symfony.com/doc/current/components/process.html)
those running in the background. And be able to cease execution (stop all the processes) in case one of them
failed.  

## Design
This package uses an event loop, currently [ReactPHP event-loop](https://github.com/reactphp/event-loop) is chosen, but this might change.  
The event loop helps in being able to start the processes one by one in non blocking manner, and allowing monitoring them for non-zero exit faster.  

## Usage
Symfony Process can be constructed using an array or a string.  
If constructed using an array, then each item will be shell escaped before forming the command line to be executed.  
If constructed using a string, then it is assumed to already be shell escaped.  
  
The second way is preferred.

On linux, exiting a running processes by signaling it works as long as the process is started using ```exec```, otherwise, it can't really be stopped.  

The best way is to construct the commands to be executed.

Example 1, no special handing.
``` php
$loop              = \React\EventLoop\Factory::create();
$newProcessFactory = new \idimsh\ParallelProcesses\NewProcessFactory();
$processesConfig   = \idimsh\ParallelProcesses\BackgroundProcessesConfig::create();

$parallel    = new \idimsh\ParallelProcesses\ParallelCliProcesses(
    $processesConfig,
    $newProcessFactory,
    $loop
);
$parallel->execWithLoop([
    'failed ls'         => \idimsh\ParallelProcesses\Command\SimpleCommand::fromString(
      'exec /bin/bash -c "ls -la /tmp/not-found"'
    )->setAsShellEscaped(true),
    
   'long failed grep exec in bash'         => \idimsh\ParallelProcesses\Command\SimpleCommand::fromString(
      'exec /bin/bash -c "sleep 3; grep --color -rHn \'random string not there\' /usr /var/"'
    )->setAsShellEscaped(true),
]);
$loop->run();
```
The two commands:
* ```ls -la /tmp/not-found```
* ```grep --color -rHn \'random string not there\' /usr /var/```   
  
Will both run in parallel, and will both fail, the long running ```grep``` will continue to run despite that ```ls``` has failed already.
Nothing will be output, since output is delegated to a logger interface and to callbacks.


## Credits

- [Abdulrahman Dimashki][link-author]
- [All Contributors][link-contributors]

## License

Released under MIT License - see the [License File](LICENSE) for details.


[ico-version]: https://img.shields.io/packagist/v/idimsh/parallel-processes.svg?style=flat-square
[ico-license]: https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square
[ico-travis]: https://img.shields.io/travis/idimsh/parallel-processes/master.svg?style=flat-square
[ico-scrutinizer]: https://img.shields.io/scrutinizer/coverage/g/idimsh/parallel-processes.svg?style=flat-square
[ico-code-quality]: https://img.shields.io/scrutinizer/g/idimsh/parallel-processes.svg?style=flat-square
[ico-downloads]: https://img.shields.io/packagist/dt/idimsh/parallel-processes.svg?style=flat-square

[link-packagist]: https://packagist.org/packages/idimsh/parallel-processes
[link-travis]: https://travis-ci.org/idimsh/parallel-processes
[link-scrutinizer]: https://scrutinizer-ci.com/g/idimsh/parallel-processes/code-structure
[link-code-quality]: https://scrutinizer-ci.com/g/idimsh/parallel-processes
[link-downloads]: https://packagist.org/packages/idimsh/parallel-processes
[link-author]: https://github.com/idimsh
[link-contributors]: ../../contributors
