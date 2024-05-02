# Library
API REST to manage books an Authors

# Create key public and private for JWT `composer require lexik/jwt-authentication-bundle`
openssl genpkey -out config/jwt/private.pem -aes256 -algorithm rsa -pkeyopt rsa_keygen_bits:4096
openssl pkey -in config/jwt/private.pem -out config/jwt/public.pem -pubout

