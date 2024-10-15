echo 'DROP DATABASE IF EXISTS shop;' | mysql -uroot -p000000
echo 'CREATE DATABASE shop;' | mysql -uroot -p000000
echo "CREATE USER IF NOT EXISTS 'shopuser'@'localhost' IDENTIFIED BY '';" | mysql -uroot -p000000
mysql -uroot -p000000 shop < `pwd`/hidden/database.sql
echo "GRANT ALL PRIVILEGES ON shop.* TO 'shopuser'@'localhost';" | mysql -uroot -p000000
echo 'FLUSH PRIVILEGES;' | mysql -uroot -p000000

php -S localhost:8080