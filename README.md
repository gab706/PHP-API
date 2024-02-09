# PHP API Template

## Important Information
This API is built using MySQL, please note this API CANNOT RUN without MySQL. You can find SQL written bellow that you can
copy and paste to create the required tables needed to run this API.

## Required SQL
**Table Name**: API_keys
```sql
CREATE TABLE `API_keys` (
  `key_id` varchar(32) NOT NULL,
  `nickname` varchar(50) NOT NULL,
  `identifier` varchar(20) NOT NULL,
  `secret` varchar(255) NOT NULL,
  `type` tinyint(1) NOT NULL DEFAULT 0,
  `created_by` varchar(32) NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `endpoint_permission` int(255) NOT NULL DEFAULT 0,
  `data_permission` int(255) NOT NULL DEFAULT 0,
  `requests` int(255) NOT NULL DEFAULT 0,
  `disabled` tinyint(1) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Table Name**: API_endpoints
```sql
CREATE TABLE `API_endpoints` (
  `endpoint_id` varchar(32) NOT NULL,
  `nickname` varchar(30) NOT NULL,
  `description` varchar(100) NOT NULL,
  `endpoint` varchar(20) NOT NULL,
  `created_by` varchar(32) NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `method` varchar(5) NOT NULL,
  `type` tinyint(1) NOT NULL,
  `permission` int(255) NOT NULL DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Table Name**: API_data
```sql
CREATE TABLE `API_data` (
  `data_id` varchar(32) NOT NULL,
  `name` varchar(30) NOT NULL,
  `endpoint` varchar(20) NOT NULL,
  `permission` int(255) NOT NULL DEFAULT 0,
  `position` int(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## How to add a Route
To add a route locally you need to do 2 things:
- First you need to create a file in the `routes` directory.
- Next you need to add this line of code into `index.php`, **NOTE** You can view the default version on Github to see an example.
```php
$Router = new RouteManager(array(
    '/test' => 'src/routes/test.php',
    // Your code here
));
```

After you have done the local part, you also need to define your route 
in the database, insert the following data into the `API_endpoints` table
- **endpoint_id** - Recommended, a 32-char UUID
- **nickname** - A nickname for your API route
- **description** - A description for your route
- **endpoint** - The endpoint for your route, for example `/test`
- **created_by** - If your API is connected to an admin panel, you can use this otherwise this column can be left empty
- **created_at** - Leave blank to get current timestamp
- **method** - Specify if it's `POST` or `GET`
- **type** - Types exists so you can have multiples types of keys, for example `OAUTH`, `Regular`. This is **NOT** coded in, however it will check if key type matches route type
- **permission** - This is a bitwise operator, say for example I have 3 routes in my Database, route 1's permission would be `1`, route 2's permission would be `2` and route 3's permission would be `4`, essentially the permission number needs to double with every route, please note you can also set it to `0` to allow everyone to access it without a key


This API can dynamically hide information from certain keys, to make this work you must all of your different information into the `API_data` table.
- **data_id** - Recommended, a 32-char UUID
- **name** - The data name, this MUST be the same as what the data key would look like in JSON format
- **endpoint** - The endpoint that this data belongs to, must match the `endpoint` from `API_endpoints`, NOT THE NICKNAME
- **permission** - This is a bitwise operator, say for example I have 3 data keys in my endpoint, key 1's permission would be `1`, key 2's permission would be `2` and key 3's permission would be `4`, essentially the permission number needs to double with every key, please note you can also set it to `0` to allow everyone to access it without a key
- **position** - A number that will dedicate the order in which your data is ordered in the API route

## How to add a Key
To add a key that can be used, you need to insert the following data into the `API_keys` table.
- **key_id** - Recommended, a 32-char UUID
- **nickname** - A nickname for your API key
- **identifier** - Your API key will be split into 2 parts and it will look like this \<identifier>.\<key>, please note we recommend a 20-char random string, **PLEASE ALSO NOTE** Identifiers must be unique
- **secret** - Your actual API key, please note your key must be hashed using an MD5 algorithm, we recommend a 32-char UUID
- **type** - The type of API key this is, make sure API key type matches endpoint type otherwise it will reject
- **created_by** - If your API is connected to an admin panel, you can use this otherwise this column can be left empty
- **created_at** - Leave blank to get current timestamp
- **endpoint_permission** - The endpoint_permission column also uses bitwise operators, say for example I have route 1, route 2 and route 3, if I want my key to be able to access all 3 routes, I need to go into the `API_endpoints` table, note down all three route's permission value, add it together and set that as my key's endpoint_permission value
- **data_permission** - The data_permission column also uses bitwise operators, say for example I have data key 1, data key 2 and data key 3, if I want my key to be able to access all 3 routes, I need to go into the `API_data` table, note down all three data key's permission value, add it together and set that as my key's data_permission value
- **requests** - Leave blank, this will log how many requests your API key makes
- **disabled** - You can disable an API key by setting this column to `1`, setting it to `0` enables the key