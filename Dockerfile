# Pinned to Debian 12 (bookworm): it ships Python 3.11, which has ready-made wheels for the pinned scikit-learn/numpy.
FROM php:8.3-apache-bookworm

RUN apt-get update \
 && apt-get install -y --no-install-recommends python3 python3-venv ca-certificates \
 && rm -rf /var/lib/apt/lists/* \
 && docker-php-ext-install mysqli pdo_mysql \
 && echo "ServerName localhost" > /etc/apache2/conf-available/servername.conf && a2enconf servername

# Python environment for the clustering models
COPY requirements.txt /tmp/requirements.txt
RUN python3 -m venv /opt/venv && /opt/venv/bin/pip install --no-cache-dir -r /tmp/requirements.txt

ENV PYTHON_PATH=/opt/venv/bin/python \
    PORT=10000 \
    PREDICT_PARALLEL=0

COPY docker/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/security.conf /etc/apache2/conf-available/security-app.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN a2enmod expires deflate && a2enconf security-app && sed -i 's/\r$//' /usr/local/bin/entrypoint.sh && chmod +x /usr/local/bin/entrypoint.sh

COPY . /var/www/html/
RUN chown -R www-data:www-data /var/www/html

EXPOSE 10000
CMD ["entrypoint.sh"]
