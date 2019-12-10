# Variables
APPENV=local
DBHOST=localhost
DBNAME=billing
DBNAME_TEST=billing_test
DBUSER=root
DBPASSWD=root

echo -e "\n--- Updating packages list ---\n"
sudo apt-get update

echo -e "\n--- Install nginx, php ---\n"
sudo apt-get install nginx -y


sudo add-apt-repository ppa:ondrej/php
sudo apt-get update
sudo apt-get install php7.0-mysql -y
sudo apt-get install php7.0-fpm -y
sudo apt-get install mc -y

sudo cp /vagrant/build/nginx.conf /etc/nginx/sites-available/billing.conf
sudo chmod 644 /etc/nginx/sites-available/billing.conf
sudo rm /etc/nginx/sites-enabled/default
sudo ln -s /etc/nginx/sites-available/billing.conf /etc/nginx/sites-enabled/billing.conf
sudo service nginx restart

sudo apt-get install php7.0-curl -y
sudo apt-get install php7.0-dom -y
sudo apt-get install php7.0-mbstring -y
sudo apt-get install php7.0-bcmath

echo -e "\n--install rabbit ---\n"
apt-get install rabbitmq-server -y > /dev/null 2>&1

curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer

echo -e "\n--install mysql ---\n"
echo "mysql-server mysql-server/root_password password $DBPASSWD" | debconf-set-selections
echo "mysql-server mysql-server/root_password_again password $DBPASSWD" | debconf-set-selections
sudo apt-get install mysql-server-5.6 -y
mysql -uroot -p$DBPASSWD -e "CREATE DATABASE $DBNAME CHARACTER SET utf8 COLLATE utf8_general_ci"
mysql -uroot -p$DBPASSWD -e "grant all privileges on $DBNAME.* to '$DBUSER'@'localhost' identified by '$DBPASSWD'"
mysql -uroot -p$DBPASSWD -e "CREATE DATABASE $DBNAME_TEST CHARACTER SET utf8 COLLATE utf8_general_ci"
mysql -uroot -p$DBPASSWD -e "grant all privileges on $DBNAME_TEST.* to '$DBUSER'@'localhost' identified by '$DBPASSWD'"

echo -e "\n--- Generate keys for git ---\n"
su vagrant -c "ssh-keygen -f ~/.ssh/id_rsa -N ''" > /dev/null 2>&1
echo -e "\n----------\n"
su vagrant -c "cat ~/.ssh/id_rsa.pub"