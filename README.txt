docker compose up --build

Visit:
- Web: http://localhost:8080
- Adminer: http://localhost:8081

Completed:
- openSUSE, Apache, PHP web environment
- MariaDB Database
- Containerized Environment

Next Steps: 
- Expand index.php
- Implement Study Hall pages (login.php, dashboard.php etc)
- Build/Implement real database/schema
- in app/public/ : 
 - db.php           # central PDO connection
 - register.php     # user signup
 - login.php        # user login/session
 - dashboard.php    # show course boards
 - new_post.php     # create a question/post
 - post.php?id=...  # view thread + comments
 - assets/          # css/js/img

Dockerfile - builds image
docker-compose - defines web/db/adminer services
vhost.conf - apache virtual host
php.ini - PHP configuration
entrypoint.sh - startup script using start_apache2
00_schema.sql - schema (replace)
10_seed.sql - seed data (replace)
index.php - home page (test page)
.htaccess - rewrite rules (enabled for now)