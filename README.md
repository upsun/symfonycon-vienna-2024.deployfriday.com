> [!CAUTION]
> ## This project is owned by the Upsun DevRel team. It has been written by Augustin Delaporte and Florent Huck for the SymfonyCon Vienna 2024 and only intended to be used with caution by Upsun customers/community.   
> This project is not supported by Upsun and does not qualify for Support plans. Use this repository at your own risks, it is provided without guarantee or warranty!

# Flawless collaboration between front and back developers

## Prerequisites

### Deploy the Symfony skeleton on Upsun

```
symfony new symfonycon-vienna-2024 --upsun
cd symfonycon-vienna-2024
symfony project:create --title symfonycon-vienna-2024
symfony deploy
```

### Configure the Symfony app with the speaker list

#### Change default `app` route to `api.{default}`
Edit your `.upsun/config.yaml` file and change existing `app` routes to `api.{default}`
```yaml {location='.upsun/config.yaml'}
routes:
    "https://api.{all}/": { type: upstream, upstream: "app:http", id: api  }
    "http://api.{all}/": { type: redirect, to: "https://api.{all}/" }
```
Then AC (git Add, Commit) your Upsun config:
```shell
git add .upsun/config.yaml && git commit -m "Upsun config: change app route to api.{default}"
```

#### Add few bundles
In order to display a list of users on the `app` frontend, we will need to add some bundles:
```shell
symfony composer require doctrine/annotations \
  doctrine/doctrine-bundle \
  doctrine/doctrine-migrations-bundle \
  doctrine/orm nelmio/cors-bundle \
  symfony/doctrine-bridge \
  symfony/html-sanitizer \
  symfony/http-client \
  symfony/intl symfony/monolog-bundle \
  symfony/security-bundle \
  symfony/serializer \
  symfony/twig-bundle \
  symfony/asset-mapper \
  symfony/asset \
  symfony/twig-pack
symfony composer require --dev doctrine/doctrine-fixtures-bundle symfony/maker-bundle
```

Then AC your changes:
```shell
git add . && git commit -m "adding required bundles: doctrine, twig, assets, ..."
```

#### Create a Speaker Entity
We will create a new entity, using [Marker Bundle](https://symfony.com/bundles/SymfonyMakerBundle/current/index.html)
```shell
symfony console make:entity
```
Add these fields:
* first_name: string(255),
* last_name: string(255),
* username: string(255),
* picture: string(1024), nullable: true
* city: string(512), nullable: true
* distance: integer, nullable: true

Then AC your changes:
```shell
git add . && git commit -m "adding Speaker entity"
```

#### Create migration files
To generate corresponding migration file for the Speaker entity, we need a database.
The DoctrineBundle comes up with a Docker container.
To start using it, execute the following:
```shell
docker compose up -d
docker ps
```
From the ``docker ps`` command, copy the external port of the `` Container and update variable `DATABASE_URL` with the right port in your `.env` file.
```shell
DATABASE_URL="postgresql://app:!ChangeMe!@127.0.0.1:57133/app?serverVersion=16&charset=utf8"
```

Then generate a migration file and update your local database using it:
```shell
symfony console doctrine:migrations:diff
symfony console doctrine:migrations:migrate
```

Then AC your changes:
```shell
git add migrations && git commit -m "adding migration for Speaker entity"
```

#### Configure Upsun ``app`` to use PostgreSQL:16
Update your ``.upsun/config.yaml`` file and add a postgresql service, PHP extension `pdo_sql` and a relationship to your ``app
```yaml {location='.upsun/config.yaml'}
services:
  database:
    type: "postgresql:16"

applications:
  app:
    #...
    runtime:
      extensions:
        # ...
        - pdo_pgsql
    #...
    relationships:
      database:
  
```
Then AC your changes:
```shell
git add .upsun/config.yaml && git commit -m "configure app to use PostgreSQL"
```

#### Add fixtures
Update existing Fixture file, in ``src/DataFixtures/AppFixtures.php`` with the following
```php
<?php

namespace App\DataFixtures;

use App\Entity\User;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    /** @var ObjectManager */
    private $objectManager;

    public function load(ObjectManager $manager): void
    {
        $this->objectManager = $manager;

        $this->createUsers();

        $manager->flush();
    }

    private function createUsers()
    {
        /* [last_name, first_name, username, city, online_picture, distance ] */
        $users = [
            ['Huck', 'Florent', 'flovntp', 'Massieux', 'https://avatars.githubusercontent.com/u/1842696?v=4', 915000],
            ['Delaporte', 'Augustin', 'guguss', 'Lyon', 'https://avatars.githubusercontent.com/u/1927538?v=4', 915001],
            ['Dunglas', 'Kevin', 'dunglas', 'Lille', 'https://avatars.githubusercontent.com/u/57224?v=4', 998000],
            ['Potencier', 'Fabien', 'fabpot', 'Moon', 'https://avatars.githubusercontent.com/u/47313?v=4', 356410002],
            //...  
        ];
        
        foreach($users as $userData) {
            $speaker = new Speaker();
            $speaker->setLastName($userData[0]);
            $speaker->setFirstName($userData[1]);
            $speaker->setUsername($userData[2]);
            $speaker->setCity($userData[3]);
            $speaker->setPicture($userData[4]);
            $speaker->setDistance($userData[5]);
            $this->objectManager->persist($speaker);
        }
        $this->objectManager->flush();
    }
}

```
Then execute it on your local database:
```shell
symfony console doctrine:fixture:load
```

Then AC your changes:
```shell
git add src/DataFixtures/AppFixtures.php && git commit -m "adding fixtures for speakers"
```

#### Add a basic frontend
First, you need to create a Controller for your homepage, in ``src/Controller/MainController.php``:
```php
<?php

namespace App\Controller;

use App\Repository\SpeakerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class MainController extends AbstractController
{
    #[Route('/', name: 'app_homepage')]
    public function homepage(SpeakerRepository $speakerRepository)
    {
        $allSpeakers = $speakerRepository->findBy([], ['id' => 'ASC']);
        
        return $this->render('main/homepage.html.twig', [
            'speakers' =>  $allSpeakers,
        ]);
    }
}
```

Then add corresponding ``templates/main/homepage.html.twig``:
```html
{% extends 'base.html.twig' %}

{% block body %}
  <div class="col-12">
    <h3>List of attendees at the SymfonyCon Vienna 2024</h3>
    <div class="divTable table table-striped table-dark table-borderless table-hover">
      <div class="divTableHeading">
        <div class="divTableRow bg-info">
          <div class="divTableHead">Picture</div>
          <div class="divTableHead">Speaker</div>
          <div class="divTableHead">City</div>
          <div class="divTableHead">Distance from Vienna</div>
        </div>
      </div>
  
      {% for speaker in speakers %}
      <div class="divTableRow">
        <div class="divTableCell">
          {% if speaker.picture %}
            <img style="height: 140px" src="{{ speaker.picture }}"/>
          {% else %}
          {# Thanks https://github.com/ozgrozer/100k-faces?tab=readme-ov-file #}
            <img style="height: 140px" src="https://randomspeaker.me/api/portraits/men/{{ speaker.id }}.jpg"/>
          {% endif %}
        </div>
        <div class="divTableCell">
          {{ speaker.firstname }} {{ speaker.lastname }} ({{ speaker.username }})
        </div>
        <div class="divTableCell">
          {{ speaker.city ?? '' }}
        </div>
        <div class="divTableCell">
          {{ (speaker.distance/1000) | number_format }} km
        </div>
      </div>
  
      {% endfor %}
    </div>
  </div>
{% endblock %}
```
A few styling of it: Modify your `assets/styles/app.css` with the following:
```css
body {
    background-color: rgb(21, 32, 43);
    color: #fff;
}

/* DivTable.com */
.divTable{
    border: 1px solid #999999;
    display: table;
    width: 100%;
}
.divTableRow {
    display: table-row;
    padding: 0.75rem;
}
.divTableCell, .divTableHead {
    display: table-cell;
    padding: 3px 10px;
}
.divTableHeading {
    background-color: #565151;
    display: table-header-group;
    font-weight: bold;
}
.divTableFoot {
    background-color: #565151;
    display: table-footer-group;
    font-weight: bold;
}
.divTableBody {
    display: table-row-group;
}

.table-dark.table-striped .divTableRow:nth-of-type(odd) {
    background-color: rgba(255, 255, 255, 0.05);
}

.table-dark.table-hover .divTableRow:hover {
    background-color: rgba(255, 255, 255, 0.075);
}

.sightingLink {
    cursor: pointer;
}

.table-dark.table-hover .sightingLink.divTableRow:hover .divTableCell {
    text-decoration: underline;
}
```
Then compile it using Symfony CLI
```shell
symfony console asset-map:compile
```

And test it on your local frontend:
```shell
symfony server:start -d
symfony open:local
```
You should see a basic list of all your speakers from the fixtures.

Then, AC your changes:
```shell
git add assets/styles/app.css src/Controller/MainController.php templates/main/homepage.html.twig && git commit -m "adding styled homepage with speaker list"
symfony deploy
```

> **Please note**: After first deploy, only your migration files are executed, but speaker table is empty.
> To load your Speaker fixtures, you can use the following command:
> ```shell
> symfony ssh -- php bin/console doctrine:fixture:load -e dev
>  ```

#### Add JSON (REST) endpoints
> **Please note**: All the steps below will prepare our Symfony application for decoupling it
> by exposing as REST endpoints the list of Speakers and the podium list (the 3 speakers the far away from the event)

We want to expose the Speaker list and the podium list.
To do so, we will create a new Controller with this 2 REST routes and create a Speaker Repository function to get the Podium.
So, first, create a new Controller ``src/Controller/SpeakerController.php``:
```php
<?php
namespace App\Controller;

use App\Repository\SpeakerRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SpeakerRestController extends AbstractController
{
    #[Route('/api/get-speaker-list', methods: ['GET'])]
    public function getSpeakerList(SpeakerRepository $speakerRepository): Response
    {
        return $this->json($speakerRepository->findBy([], ['id' => 'ASC']));
    }

    #[Route('/api/get-podium', methods: ['GET'])]
    public function getPodium(SpeakerRepository $speakerRepository): Response
    {
        return $this->json($speakerRepository->getSpeakerPodium());
    }
}
```

Then add a new ``getSpeakerPodium`` function in your `src/Repository/SpeakerRepository.php` to fetch all the speakers:

```php
<?php

namespace App\Repository;

use App\Entity\Speaker;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/** @extends ServiceEntityRepository<Speaker> */
class SpeakerRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Speaker::class);
    }
    
    public function getSpeakerPodium()
    {
        return $this->createQueryBuilder('s')
            ->orderBy('s.distance', 'DESC')
            ->setMaxResults(3)
            ->getQuery()
            ->getArrayResult();
    }
}
```

And test the 2 new endpoints [/api/get-speaker-list](https://localhost:8000/api/get-speaker-list) and [/api/get-podium](https://localhost:8000/api/get-podium)

Then, AC your changes:
```shell
git add src/Controller/SpeakerController.php src/Repository/SpeakerRepository.php && git commit -m "adding REST endpoint (Json) for speaker list and podium"
```

#### Add sanitization of Upsun preview environments
In order to not expose production data to potential external member of your company working on your project (preview environment), we will setup our project to sanitize preview databases on the fly during deploy hook.

First create a new command to sanitize data, in ``src/Command/SanitizeDataCommand.php``:
```php
<?php
/* src/Command/SanitizeDataCommand.php */

namespace App\Command;

use App\Entity\Speaker;
use App\Repository\SpeakerRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sanitize-data',
    description: 'Sanitize speaker data (first_name, last_name, username and picture).',
    aliases: ['app:sanitize']
)]
class SanitizeDataCommand extends Command
{
    private SymfonyStyle $io;

    public function __construct(private SpeakerRepository $speakerRepository, private EntityManagerInterface $entityManager)
    {
        parent::__construct();
    }

    protected function initialize(InputInterface $input, OutputInterface $output): void
    {
        $this->io = new SymfonyStyle($input, $output);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $speakers = $this->speakerRepository->findAll();
        $this->io->progressStart(count($speakers));

        $this->entityManager->getConnection()->beginTransaction(); // suspend auto-commit
        try {
            /** @var Speaker $speaker */
            foreach ($speakers as $speaker) {
                $this->io->progressAdvance();
                // fake user info
                $speaker->setLastName('Wick');
                $speaker->setFirstName('John');
                $speaker->setUsername(uniqid('john-wick-'));
                $speaker->setPicture('https://cdna.artstation.com/p/assets/images/images/004/943/296/large/andrey-pankov-neo.jpg?1487365474');
            }
            $this->entityManager->flush();
            $this->entityManager->getConnection()->commit();
            $this->io->progressFinish();
        } catch (\Exception $e) {
            $this->entityManager->getConnection()->rollBack();
            throw $e;
        }

        return Command::SUCCESS;
    }
}
```

Then, we need to tell Upsun to execute this Symfony command during the deploy hook.
Modify your ``.upsun/config.yaml`` file and add the following at the end of the existing `hooks.deploy` block:
```yaml
applications:
    app:
        #...
        hooks:
            #...
            deploy: |
                set -x -e

                symfony-deploy

                # The sanitization of the database if it's not production
                if [ "$PLATFORM_ENVIRONMENT_TYPE" != production ]; then
                    php bin/console app:sanitize-data
                fi
```
> **Please note**: in our case, our database is small, and so, sanitizing data during the deploy hook is not a big deal, but if you want so more advance technics, please refer to [this blogpost](https://upsun.com/blog/how-to-sanitize-preview-environment-data/)

Finally, AC your changes and deploy:
```shell
git add src/Command/SanitizeDataCommand.php .upsun/config.yaml && git commit -m "adding automatic sanitization of data on preview envs"
symfony deploy
```

Ready to start!!