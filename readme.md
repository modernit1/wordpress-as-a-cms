Wordpress as CMS for Static Content
=====================================
This project is started with a general premise that most organizations needs for websites align with a serverless architecture approach.  It is the intent of this project to provide the building blocks to for organizations to use this approach to build a secure and stable website.


----------


Content Management System
-------------

Wordpress very nice Content Management System [CMS], which easily creates a website.  The disadvantages to running WordPress for an Organizations website are:
> - The cost of hosting a dynamic server(s).
> -  Wordpress being dynamic is a history of security exploits.

This project intends to solve these problems by providing the Wordpress CMS as a docker container you run locally to generate the website and publish it to S3.

#### Build a Container
To build a new copy of the container after modifying code simply run docker build. --tag $new_container_name