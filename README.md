apFramework
===========

Yet another half baked but useful in many cases MVC PHP framework



What is it?
-----------

The apFramework project is a PHP MVC framework orinigally developed
as an internal set of utilities within the development of the
Apfutura Internacional Soluciones S.L. web project "apex". As it grew
in size and they were reused for other developments it was decided to
make them a separete project.



Documentation
-------------

The documentation available as of the date of this release is
included the docs/ directory.



Installation
------------

Any PHP webserver installation supporting PHP 5.3+ should be enough to
use the framework. Refer to the documentation to start developing using it.
  
apFramework comes with the following folder/files strucure:
  
- index.php
Framework index file. No modifications should be made to it.
  
- controllers/
Folder for the user project controllers files. By default, the index.php controller and operation get within are called, and thus suplied as example
   
- ap/
Folder with the framework code.
User project using the framework might consider personalizing the following inside it:
  
- ap/config/setup.php [IMPORTANT!]
Configuration file. If the user project using is not server in the base folder of the web server $urlBase should be set to the correct url base.


This are the folders included as suggested best folder structure for the user project using apFramewrok, but its use is optional:
     
- models/
Empty folder for the user project classes.
  
- templates/
Empty folder for the user project templates.
  
- css/
Empty folder for the user project css.
  
- js/
Empty folder for the user project js.
  
- js/libs/
Empty folder for the user project 3d party js libraries.
  
- images/
Empty folder for the user project images.
    
      

Licensing
---------

Please see the file called LICENSE.
  


Contacts
--------

- If you want to contact the main author please either contact
  Apfutura Internacional S.L. using the contact us section at
  http://www.apfutura.net or write to rosa.toni@apfutura.net
