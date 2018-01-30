# RDS
A deployment management tool written by PHP and yii 2 with web interface and command line build agents

[More documentation about RDS](https://github.com/Whotrades/RDS/blob/master/docs/basic.md) 

## Features
 * Full control of deployment process (from creating version bundle
 and uploading to servers to removing it)
 * WEB interface for control deployment process written using bootstrap
 * Support any PHP frameworks or projects
 * Working with many application servers at one time
 * Deployment process contains steps:
   * Building version bundle
   * Uploading version bundle to list of servers   
   * Execution SQL migrations
   * Uploading CRON configuration
   * Activation version bundle or reverting to previous one
   * Removing version bundle from servers after some time 

## Dependencies
RDS components:
 * Postgres 9.3+
 * PHP 7.0+
 * RabbitMq 3.3+
 
# Installation via vagrant
 * Clone project
 * Run ``cd install/vagrant && vagrant up``
 * Visit http://localhost:8085/

# Installation manually
 * Clone project
 * Run ```composer install```
 * Create database at postgres
 * Create user and vhost at RabbitMq
 
## Contributing
You are able to create any pull request and ask for merging it

## License
Licensed under the [MIT license](https://github.com/Whotrades/RDS/blob/master/LICENSE).
