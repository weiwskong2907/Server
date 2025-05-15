FROM php:8.2-apache

# Install required packages
RUN apt update && apt install -y libapache2-mod-php git openssl

# Enable Apache SSL and rewrite modules
RUN a2enmod ssl rewrite

# Create a self-signed certificate
RUN mkdir /etc/apache2/ssl && \
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/apache2/ssl/apache.key \
    -out /etc/apache2/ssl/apache.crt \
    -subj "/C=US/ST=State/L=City/O=Org/OU=Dev/CN=localhost"

# Copy and enable SSL config
COPY apache-ssl.conf /etc/apache2/sites-available/default-ssl.conf
RUN a2ensite default-ssl

EXPOSE 443