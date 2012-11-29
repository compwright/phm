PHM: PHP Multiprocessor
=======================

A collection of classes for leveraging PHP's multi-processing and process control capabilities.

Author: Jonathon Hill
License: MIT

Dependencies
------------
* PHP 5.3+
* PHP PCNTL extension
* PHP Semaphore extension

### Dependency installation

If your PHP binary was not compiled with `--enable-pcntl`, you may need to recompile PHP. To do this,
download and extract the PHP source code and run the following:

```
cd php-5.3.15/
./configure --prefix=/usr --mandir=/usr/share/man --infodir=/usr/share/info --disable-dependency-tracking --sysconfdir=/private/etc --with-apxs2=/usr/sbin/apxs --enable-cli --with-config-file-path=/etc --with-libxml-dir=/usr --with-openssl=/usr --with-kerberos=/usr --with-zlib=/usr --enable-bcmath --with-bz2=/usr --enable-calendar --disable-cgi --with-curl=/usr --enable-dba --enable-ndbm=/usr --enable-exif --enable-ftp --with-icu-dir=/usr --with-iodbc=/usr --with-ldap=/usr --with-ldap-sasl=/usr --with-libedit=/usr --enable-mbstring --enable-mbregex --with-mysql=mysqlnd --with-mysqli=mysqlnd --with-pdo-mysql=mysqlnd --with-mysql-sock=/var/mysql/mysql.sock --with-readline=/usr --enable-shmop --with-snmp=/usr --enable-soap --enable-sockets --enable-sqlite-utf8 --enable-suhosin --enable-sysvmsg --enable-sysvsem --enable-sysvshm --with-tidy --enable-wddx --with-xmlrpc --with-iconv-dir=/usr --with-xsl=/usr --enable-zend-multibyte --enable-zip --with-pgsql=/usr --with-pdo-pgsql=/usr --enable-pcntl
make
sudo make install
```
