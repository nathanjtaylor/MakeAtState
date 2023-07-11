FROM docker.io/php:7.4-apache AS base

RUN apt update \
    && DEBIAN_FRONTEND=noninteractive apt install -y --no-install-recommends \
        git \
        msmtp \
        unzip \
    && rm -rf /var/lib/apt/lists/* \
    && curl -sS https://getcomposer.org/installer \
        | php -- --install-dir=/usr/local/bin --filename=composer \
    && docker-php-ext-install pdo_mysql

RUN echo "sendmail_path = /usr/bin/msmtp -t" \
        >/usr/local/etc/php/conf.d/msmtp_sendmail.ini

RUN sed -e 's/max_execution_time = 30/max_execution_time = 120/' \
        -e 's/post_max_size = 8M/post_max_size = 128M/' \
        -e 's/upload_max_filesize = 2M/upload_max_filesize = 128M/' \
        /usr/local/etc/php/php.ini-production >/usr/local/etc/php/php.ini

RUN touch /etc/apache2/sites-available/app.conf \
    && a2dissite 000-default \
    && a2ensite app \
    && a2enmod rewrite ssl

ENTRYPOINT ["/entrypoint"]
CMD ["apache2-foreground"]
HEALTHCHECK --start-period=30s --interval=10s --timeout=30s \
    CMD curl --fail http://localhost

WORKDIR /var/www/MakeAtState

################################################################################

FROM base AS build

# Copying a file from disk directly into the image creates two instances of the
# full file: the first from the COPY, the second from the mode change of the
# file. Copying files into this temporary build stage and performing the mode
# change here leaves only one instance of each file in the final image.

COPY --chown=root:root apache.conf /etc/apache2/sites-available/app.conf
RUN chmod 0644 /etc/apache2/sites-available/app.conf

COPY --chown=root:root entrypoint /entrypoint
RUN chmod 0755 /entrypoint

# We run Composer with only the Composer configuration so we can pull the vendor
# dependencies without the layer depending on the rest of the source code.
COPY composer.json composer.lock /var/www/MakeAtState/
RUN composer install --no-cache

COPY . /var/www/MakeAtState
RUN chmod -R u=rwX,go=rX /var/www/MakeAtState

################################################################################

FROM base

COPY --from=build /etc/apache2/sites-available/app.conf \
    /etc/apache2/sites-available/app.conf
COPY --from=build /entrypoint /entrypoint
COPY --from=build /var/www/MakeAtState /var/www/MakeAtState

RUN composer install --optimize-autoloader --no-cache
