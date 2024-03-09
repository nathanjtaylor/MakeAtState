# MakeAtState

MakeAtState is an open-source software application written using PHP to manage fabrication project submissions. The website allows users to upload files for 3D printing, laser cutting, CNC routing, etc. Staff use the application to keep track of the jobs submitted by users, download files, provide a price and delivery date estimate, and notify the users when the project is ready for pick up. Communication with the user about their project is made easy through a messaging feature. 

## Features
Modifiable Project Workflow
Add as many steps as needed. Set the price, estimated delivery date, and close order when completed. Cancellation options for user and staff and ability to control when users have the option to cancel. Optional email notification for each step. Different descriptive text for user and staff.

### Add Multiple Workflow Types
Add a separate workflow for 3D printing, laser cutting, class submissions, etc. Separate workflows by group and set the allowed file extensions. Each workflow can have multiple “printers” or machine choices. Each printer has its own material, color, and variable price options.

### User submissions
Users can create an account through single sign on or via a verified email address. Files can be uploaded stored in the account for a set period of time before expiring. Multiple files can be added to a project submission, users choose workflow, printer, material and color, specify dimensions, and pick up options.

### Staff Process
Jobs are divided into New, Held, Open, and Closed tabs. Initial submissions are in the New section, once staff take action it moves to the Open tab. Jobs can be placed on hold at any stage of the process if there is an issue or action is needed from the user. Once projects are completed, they are moved to Closed.
Each project has three pages. The first is the Job Info page with status, invoice details, user information, and next action buttons. Next is the Job Messages page which is where communications can be sent back and forth. Images can be attached to the message and users get an email notification that there is a new message. The Job Updates page shows a history of the completed actions with dates and a timestamp. Staff names are concealed to users for privacy.

### Admin Features
Admins control the Project Workflow and Workflow Types with their respective options. Admins can set account access between user, student staff, staff, or admin. Users can be blocked if needed. Allocated file storage space and expiration dated can be modified. Stats are visible for analytics

## Limitations
All workflows have tax built into the price calculation and there is no tax exempt option. Automated emails are limited in customization. The jobs page can be cumbersome to read quickly so we still use a more detailed queue spreadsheet in addition to the website. Some users have trouble understanding the submission process and experience frustration.

## Prerequisites
* PHP version 7.0 or higher

## Docker setup

* Update the apt package index and install packages to allow apt to use a repository over HTTPS (This is recommended by the official installation guide)
```
sudo apt update
sudo apt install apt-transport-https ca-certificates curl gnupg-agent software-properties-common
```
* Add Dockers's GPG Key
```
sudo curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo apt-key add -
sudo add-apt-repository "deb [arch=amd64] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable"
```
* Install Docker's supporting packages
```
sudo apt-get update
sudo apt-get install docker-ce docker-ce-cli containerd.io
```
* A successful installation should give a manual page when `man docker` is run.

## Docker swarm setup
* Swarm is already included with Docker installation, there is no need to install it separately
* For any given Swarm, you will need to ensure network communication is allowed between the nodes:

- TCP port 2377 for cluster management communications
- TCP and UDP port 7946 for communication among nodes (not required in our setup as our manager node is also the worker node)
- UDP port 4789 for overlay network traffic (not required in our setup)
```
sudo ufw allow from <IP> to any port 2377 proto tcp
```
* Initialize the Swarm (manager node only)
```
docker swarm init --advertise-addr <IP> --listen-addr <IP>:2377
```
* Ensure that all the developers are in the docker group. Add if necessary.
```
usermod -a -G docker [netid]
```
* Verify the swarm using `docker node ls`. A manager node should be present. In our case the manager node is also the worker node.

## Build images and stack
* `cd` into the repository.
* Build MakeAtState image using
```
# cmd for test instance
docker build --no-cache . -t makeatstate:latest
```
* Deploy the stack using
```
docker stack deploy -c docker-compose.yml -c docker-compose.devel.yml makeatstate
```
* Remove the stack using
```
docker stack rm makeatstate
```

## Setup Database

* Run the `table_definitions.sql` file to create all the necessary tables.
```
docker exec -i {db_container_id} mysql  makeatstate < table_definitions.sql
```
* Run the following scripts to insert permissions and workflow step types.

```
docker exec -i {db_container_id} mysql  makeatstate < initial_db_setup.sql
```

# Config file
* Create a config file in your project root level folder `makeatstate.cfg` and insert the following configurations.

```
####################################
## Application Config Settings
###################################


####################################
## Application Config Settings
###################################


[application]
timezone 	= "America/Detroit"
url		= "http://application_url"
email 		= "root@server.address"
updates_email   = "admin_email@test.edu"
owner 		= "application_owner@test.edu"
usps_id = "usps_api_id"

[authentication.okta]
issuer          = "https://okta.endpoint"
client_id       = "okta.client_id"
client_secret   = "okta.client_secret"
redirect_url    = "http://application_url/?t=okta-authorization-code-callback"
active          = 1

[sql.auth.primary]
user		= makeatstate
password 	= password
database 	= makeatstate
host		= database
port		= 3306

[logging]
file = "/tmp/MakeAtState.log"

[upload]
path 		= "/mnt/makeatstate/files"
attachment_path = "/mnt/makeatstate/attachments"
attachment_ext  = "png"
attachment_ext  = "jpg"
attachment_ext  = "jpeg"
ext = "stl"
ext = "jpg"
ext = "jpeg"
ext = "png"
ext = "svg"
ext = "pdf"
ext = "tiff"
ext = "tif"
ext = "ai"



# views:
#   0 - no view
#   1 - three.js
#   2 - browser

[file_type]
stl.ext = "stl"
stl.mime_type            = "application/octet-stream"
stl.mime_type            = "application/vnd.ms-pki.stl"
stl.mime_type            = "application/x-navistyle"
stl.mime_type            = "application/sla"
stl.view = 1
stl.downloadable

jpeg.ext         = "jpeg"
jpeg.mime_type            = "image/jpeg"
jpeg.mime_type            = "image/x-citrix-jpeg"
jpeg.view = 2
jpeg.downloadable

jpg.ext         = "jpg"
jpg.mime_type            = "image/jpeg"
jpg.mime_type            = "image/x-citrix-jpeg"
jpg.view = 2
jpg.downloadable


png.ext         = "png"
png.mime_type = "image/png"
png.view = 2
png.downloadable

svg.ext = "svg"
svg.mime_type            = "image/svg+xml"
svg.view = 2
svg.downloadable

pdf.ext         = "pdf"
pdf.mime_type            = "application/pdf"
pdf.view = 2
pdf.downloadable

tiff.ext         = "tiff"
tiff.mime_type            = "image/tiff"
tiff.mime_type            = "image/x-tiff"
tiff.view = 0
tiff.downloadable

tif.ext         = "tif"
tif.mime_type            = "image/tiff"
tif.mime_type            = "image/x-tiff"
tif.view = 0
tif.downloadable

ai.ext         = "ai"
ai.mime_type            = "application/postscript"
ai.view = 0
ai.downloadable

#type            = "application/octet-stream"

[app.start_year]
start_year 	= 2018 // get the starting year for analytics

[app.storage]
268435456 //256 MB
536870912 //512 MB
1073741824 //1 GB
2147483648 //2GB

[workflow_tags]
3dprint.help_url = "https://helptext_url"
3dprint.url_text = "MakeCentral Makerspace 3D Printing"
3dprint.dimensions_placeholder ="e.g. 16mmx70mmx30.4mm or 401.89mm on the longest side"
vinyl.help_url = "https://helptext_url"
vinyl.url_text = "MakeCentral Makerspace Vinyl cutting"
vinyl.dimensions_placeholder = "List the size of your object excluding any blank space surrounding the design. Ex. 10in x 3.9in"
laser.help_url = "https://helptext_url" 
laser.url_text = "MakeCentral Makerspace Laser cutting"
laser.dimensions_placeholder = "Please format your file so your object is on a 12x12in or 12x20in sized file"
default.help_url = "https://helptext_url" 
default.url_text = "MakeCentral Makerspace"

[app.affiliation]
1		= "Community user"
2		= "Faculty"
3		= "Staff"
4		= "Student"
```
