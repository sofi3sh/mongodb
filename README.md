# MongoDB Two Bots

Два боти(основний та дочірній), які здійснюють обмін даними між собою за допомогою API, яка знаходиться у дочірньому боті

## Початок роботи

Для початку роботи треба розвернути обидва боти на сервері, встановити залежності(composer install) та запустити основний бот(перейти по роуту 
http://{ НАЗВА ОСНОВНОГО БОТУ }/apimongo/script/getOrdersMongoCron. Після цього відбудеться обмін данними 

## Методи 
- Всі методи, пов'язані з API, лежать у controller/ControllerDBApi.php(дочірнього боту)
- Всі методи, пов'язані з діями з основною БД, лежать у controller/ControllerRequestMongoApi.php(основного боту) 
