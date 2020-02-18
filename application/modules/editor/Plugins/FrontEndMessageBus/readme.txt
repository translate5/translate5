# until it is clear how we embed all the dependencies into translate5 we have to do the following for installation:

# go into the bus-server directory
cd bus-server

# Install composer with the following code (code from https://getcomposer.org/download/): 

php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
php -r "if (hash_file('sha384', 'composer-setup.php') === 'a5c698ffe4b8e849a443b120cd5ba38043260d5c4023dbf93e1558871f1f07f58274fc6f4c93bcfd858c6bd0775cd8d1') { echo 'Installer verified'; } else { echo 'Installer corrupt'; unlink('composer-setup.php'); } echo PHP_EOL;"
php composer-setup.php
php -r "unlink('composer-setup.php');"

# Install all needed dependencies
php composer.phar update

# go back to application/modules/editor/Plugins/FrontEndMessageBus
cd ..

# start MessageBus Server:
php server.php
