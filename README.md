# FastLab

FastLab es una herramienta de administración para servidores Linux que te permite gestionar fácilmente una estación de trabajo para el desarrollo. Con FastLab, se puede crear rápidamente espacios para múltiples usuarios, en donde cada uno dispone de herramientas y tecnologías necesarias para crear y depurar prototipos de manera rápida y sencilla.

FastLab está desarrollado en PHP para el backend. HTML5 y Javascript con el framework applicacions.js para el frontend.

## Instalación

Para instalar FastLab, debes tener instalado en tu sistema operativo Linux los siguientes paquetes:

```bash
sudo apt-get update && apt-get install php supervisor git nginx apache2 mariadb-server
```

Descargar el repositorio de FastLab:

```bash
git clone https://github.com/carlosbarcala/fastlab.git
```

### Configurar Nginx

Copiar el archivo de configuración de Nginx de FastLab en la carpeta de configuración de Nginx y a continuación edita el archivo y sustituye [dir] por la ruta donde se encuentra la carpeta de FastLab.

```bash
sudo cp fastlab/conf/nginx/fastlab.conf /etc/nginx/sites-available/
sudo ls -s /etc/nginx/sites-available/fastlab.conf /etc/nginx/sites-enabled/
```
### Configurar Apache

Copiar el archivo de configuración de Apache de FastLab en la carpeta de configuración de Apache y a continuación edita el archivo y sustituye [dir] por la ruta donde se encuentra la carpeta de FastLab.

```bash
sudo cp fastlab/apache/fastlab.conf /etc/apache2/sites-available/
sudo a2ensite fastlab.conf
```

### Configurar Supervisor para ejecutar el servidor de comandos

Para configurar el servidor, debes ejecutar copiar el archivo de configuración de FastLab en la carpeta de configuración de supervisor y a continuación edita el archivo y sustituye [dir] por la ruta donde se encuentra la carpeta de FastLab:

```bash
sudo cp fastlab/fastlab.conf /etc/supervisor/conf.d/
```

Iniciar el servidor:

```bash
sudo supervisorctl start fastlab
```

Para detener el servidor:

```bash
sudo supervisorctl stop fastlab
```

## A tener en cuenta

Tendrás que tener activado sudo y añadir al menos un usuario al grupo sudo para poder utilizar FastLab. 


FastLab está en desarrollo, por lo que no se recomienda su uso en producción. FastLab está desarrollado para sistemas operativos Linux, por lo que no se garantiza su funcionamiento en otros sistemas operativos.



