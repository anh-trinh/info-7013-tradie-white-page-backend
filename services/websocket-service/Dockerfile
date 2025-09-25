FROM node:18-alpine
WORKDIR /usr/src/app
COPY package.json package-lock.json* ./
RUN npm ci --only=production || npm i --production
COPY . .
EXPOSE 4000
CMD ["node", "index.js"]
