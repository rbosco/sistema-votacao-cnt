# =====================================================
# Base: PHP 8.3 + Apache (Debian 11 bullseye)
# =====================================================
FROM php:8.3-apache-bullseye

LABEL maintainer="Equipe SRT-CNT"
ENV DEBIAN_FRONTEND=noninteractive
ENV ACCEPT_EULA=Y
ENV TZ=America/Sao_Paulo

# -----------------------------------------------------
# Instala dependências essenciais e extensões PHP
# -----------------------------------------------------
RUN apt-get update && apt-get install -y \
    apt-transport-https \
    ca-certificates \
    curl \
    gnupg2 \
    unixodbc-dev \
    build-essential \
    libxml2-dev \
    libzip-dev \
    zip unzip git iputils-ping locales supervisor nano \
 && docker-php-ext-install pdo pdo_mysql bcmath zip intl opcache \
 && rm -rf /var/lib/apt/lists/*

# -----------------------------------------------------
# Configuração de locale e timezone
# -----------------------------------------------------
RUN echo "en_US.UTF-8 UTF-8" > /etc/locale.gen \
 && locale-gen \
 && echo "${TZ}" > /etc/timezone \
 && ln -snf /usr/share/zoneinfo/${TZ} /etc/localtime

# -----------------------------------------------------
# Repositório e drivers do SQL Server (msodbcsql18 + sqlsrv)
# -----------------------------------------------------
RUN mkdir -p /etc/apt/keyrings \
 && curl -fsSL https://packages.microsoft.com/keys/microsoft.asc \
    | gpg --dearmor -o /etc/apt/keyrings/microsoft.gpg \
 && echo "deb [arch=amd64,arm64 signed-by=/etc/apt/keyrings/microsoft.gpg] https://packages.microsoft.com/debian/11/prod bullseye main" \
    > /etc/apt/sources.list.d/mssql-release.list \
 && apt-get update \
 && ACCEPT_EULA=Y apt-get install -y msodbcsql18 mssql-tools18 \
 && echo 'export PATH="$PATH:/opt/mssql-tools18/bin"' >> /etc/profile \
 && pecl install sqlsrv pdo_sqlsrv \
 && docker-php-ext-enable sqlsrv pdo_sqlsrv \
 && rm -rf /var/lib/apt/lists/*

# -----------------------------------------------------
# Habilita Apache rewrite e configura virtualhost
# -----------------------------------------------------
RUN a2enmod rewrite
COPY docker_mte/extra/host.conf /etc/apache2/conf-enabled/host.conf
COPY docker_mte/extra/000-default-backend.conf /etc/apache2/sites-enabled/000-default.conf
COPY docker_mte/extra/apache2.conf /etc/apache2/apache2.conf

# -----------------------------------------------------
# Configura PHP
# -----------------------------------------------------
COPY docker_mte/extra/production.ini /usr/local/etc/php/conf.d/production.ini

# Ajusta parâmetros PHP
RUN sed -i \
    -e 's/memory_limit = .*/memory_limit = 1024M/' \
    -e 's/max_execution_time = .*/max_execution_time = 300/' \
    -e 's/post_max_size = .*/post_max_size = 512M/' \
    -e 's/upload_max_filesize = .*/upload_max_filesize = 512M/' \
    /usr/local/etc/php/conf.d/production.ini

# -----------------------------------------------------
# Instala Composer
# -----------------------------------------------------
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# -----------------------------------------------------
# Define diretório do app e copia código
# -----------------------------------------------------
WORKDIR /var/www/html
COPY . /var/www/html

# -----------------------------------------------------
# Instala dependências Laravel
# -----------------------------------------------------
RUN composer install --no-interaction --prefer-dist --optimize-autoloader

RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
  && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache


# -----------------------------------------------------
# Entrypoint customizado (opcional)
# -----------------------------------------------------
COPY docker_mte/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

EXPOSE 80
CMD [ "sh", "-c", "/entrypoint.sh && apache2-foreground" ]
