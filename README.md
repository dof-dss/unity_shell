# Unity Shell

Unity Shell is a command line tool to manage Unity2 projects and sites.

# Requirements
- A computer
- PHP 8+ CLI
- Composer 2.1+

### PHP

To see which version of PHP you have installed, from the Mac shell run: 
```shell
php --version
```
If you don't have PHP installed or are using an older version I would 
recommend using Brew (https://brew.sh)  
With Brew installed run:
```shell
brew install php
```

### Composer

To see which version of Composer you have installed, from the Mac shell run:
```shell
composer --version
```
If you don't have Composer installed, using brew run:
```shell
brew install composer
```
If you do have Composer installed run: 
```shell
composer self-update
```

# Installing

First clone this repository locally (See Unity2 Confluence document for 
recommended Unity2 directory structure).
```shell
git clone https://github.com/dof-dss/unity_shell.git
```
From the unity_shell repository run:
```shell
composer install
```

To allow use of the Unity Shell command from any Unity2 fork without having to 
directly reference the Unity Shell 
executable I recommend adding the repository binary directory to your shell 
$PATH.  
As an example I'm using zsh which is the default shell for MacOS 

Edit .zshrc in your user directory and add the following line (replacing the 
first part with the path to the repo)

```shell
# Unity Shell
export PATH="/<YOUR PATH TO UNITY SHELL>/bin:$PATH"
```

Once saved you will need to run 
```shell
source ~/.zshrc
```

# Usage

If you have added Unity Shell to your shell path you can simply shell into a 
local Unity2 server fork and run 
```shell
unitysh 
``` 
Which will provide a list of commands, the majority of which you can ignore, 
the interesting ones are listed in the next 
section.

# Commands

project:create -- Create new Unity server project.   
project:build -- Build the current project.  
project:info -- Display information about the project.

site:add -- Add a new site to the project.  
site:remove -- Remove a site from the project.  
site:edit -- Edit details of an existing site.
