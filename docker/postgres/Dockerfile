FROM postgres:16-alpine

# Устанавливаем дополнительные пакеты, если нужны
RUN apk add --no-cache postgresql-client

# Копируем скрипты инициализации (опционально)
# COPY init.sql /docker-entrypoint-initdb.d/

# Директория данных
ENV PGDATA=/var/lib/postgresql/data

# PostgreSQL запускается от пользователя postgres (встроенный в образ)
# Ничего больше не нужно - всё уже правильно настроено
