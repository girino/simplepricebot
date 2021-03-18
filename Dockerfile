FROM php:7.4-cli

# Create app directory
WORKDIR /usr/src/app

COPY main.php ./

CMD [ "php", "main.php" ]
