FROM node:20-alpine

WORKDIR /var/www/html

# Instala dependências do sistema
RUN apk add --no-cache git

# Copia package files (agora do contexto raiz)
COPY package*.json ./

# Instala dependências
RUN npm ci

# Copia configurações e recursos necessários
COPY vite.config.js ./
COPY resources/ ./resources/
COPY public/ ./public/

# Expõe porta do Vite dev server
EXPOSE 5173

# Comando padrão para development
CMD ["npm", "run", "dev"]
