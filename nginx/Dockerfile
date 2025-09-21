FROM nginx:stable-alpine

# remove default site
RUN rm /etc/nginx/conf.d/default.conf

# copy custom config
COPY nginx.conf /etc/nginx/conf.d/
