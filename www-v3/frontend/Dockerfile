FROM node:20-alpine AS build
WORKDIR /src
COPY package.json ./
RUN npm install --no-fund --no-audit
COPY . .
RUN npm run build

FROM busybox:1.36
WORKDIR /out
COPY --from=build /src/dist /out
CMD ["sh", "-c", "cp -r /out/. /var/www/html/ && echo 'frontend build copied'"]
