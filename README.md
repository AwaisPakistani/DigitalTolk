1. git clone https://github.com/AwaisPakistani/DigitalTolk.git
2. Run command (cp .env.example .env )
3. Run command ( composer install )
4. Then generate key to run command ( php artisan key:generate )
5. Now lastly run command ( php artisan migrate:fresh --seed )
6. Now run project by ( php artisan serve )
7. Now as I'm using sanctum package for token based authentication 
   so run api using ( http://127.0.0.1:8000/api/login) to login first.
8. Now you can run api's on thender client extension on vscode ide  or postman using desktop agent to see in api.php file that we can create,update, delete translation records 
9. And you can use translation in frontend framework like vue.js or 
   Next.js you want.
