FROM nginx:1.23
ADD provision/docker-images/nginx/nginx.conf /etc/nginx/conf.d/default.conf

#ADD src/Application/Lumen/public/js /var/www/html/Application/Lumen/public/js
#ADD src/Application/Lumen/public/css /var/www/html/Application/Lumen/public/css
#ADD src/Application/Lumen/public/fonts /var/www/html/Application/Lumen/public/fonts
#ADD src/Application/Lumen/public/img /var/www/html/Application/Lumen/public/img

#ADD src/vue3/dist /var/www/html

RUN echo "upstream php-upstream { server api:9000; }" > /etc/nginx/conf.d/upstream.conf

RUN usermod -u 1000 www-data

WORKDIR /etc/nginx

EXPOSE 80
EXPOSE 443
