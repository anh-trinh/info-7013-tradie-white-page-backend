FROM nginx:stable-alpine

# Remove default site
RUN rm -f /etc/nginx/conf.d/default.conf

# Copy local docker site config as default
COPY nginx.conf /etc/nginx/conf.d/default.conf

# Also provide Railway-friendly configs (optional)
COPY templates/railway.nginx.conf /etc/nginx/nginx.conf
COPY templates/site.conf /etc/nginx/conf.d/site.conf
