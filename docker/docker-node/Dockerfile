FROM node:20-alpine

WORKDIR /var/www

# устанавливаем зависимости ОДИН раз и кэшируем слой
COPY src/package*.json ./
RUN npm ci --no-audit --no-fund

# код монтируется как volume, команды — ниже в compose
CMD ["npm","run","dev"]
