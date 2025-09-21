FROM nginx:stable-alpine

# Remove default site
RUN rm -f /etc/nginx/conf.d/default.conf

# Local (docker-compose) uses bind mount to provide nginx.conf at conf.d/default.conf
# For Railway, use the official entrypoint's envsubst to render from a template
COPY templates/default.conf.template /etc/nginx/templates/default.conf.template
