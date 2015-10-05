<?php

namespace AndrewCarterUK\APOD;

use GuzzleHttp\ClientInterface;
use Intervention\Image\ImageManagerStatic;

class API implements APIInterface
{
    /**
     * @var ClientInterface 
     */
    private $client;

    /**
     * @var array
     */
    private $options;

    /**
     * 
     * @param ClientInterface $client
     * @param array $options
     */
    public function __construct(ClientInterface $client, array $options)
    {
        $this->client = $client;

        $this->options = array_replace([
            'timezone'         => 'America/New_York',
            'api_key'          => 'DEMO-KEY',
            'endpoint'         => 'https://api.nasa.gov/planetary/apod',
            'start_date'       => '1995-06-16',
            'thumbnail_width'  => 300,
            'thumbnail_height' => 200,
        ], $options);

        $requiredOptions = ['store_path', 'base_url'];

        foreach ($requiredOptions as $option) {
            if (!isset($this->options[$option])) {
                throw new \InvalidArgumentException($option.' must be set');
            }
        }

        $this->options['store_path'] = rtrim($this->options['store_path'], '/').'/';
        $this->options['base_url']   = rtrim($this->options['base_url'],   '/').'/';
    }

    /**
     * {@inheritDoc}
     */
    public function getPage($page = 0, $numberPerPage = 24)
    {
        $dateTime = $this->getDateTime();

        $start = $page * $numberPerPage;

        $pictures = [];

        for ($offset = $start; $offset < $start + $numberPerPage; $offset++) {
            $pictureDateTime = clone $dateTime;
            $pictureDateTime->sub(new \DateInterval('P'.$offset.'D'));
            $pictureDate = $pictureDateTime->format('Y-m-d');

            if ($this->isPictureStored($pictureDate)) {
                $pictures[$pictureDate] = $this->getStoredPicture($pictureDate);
            }
        }

        return $pictures;
    }

    /**
     * {@inheritDoc}
     */
    public function updateStore(callable $newPicture = null, callable $errorPicture = null)
    {
        $dateTime       = $this->getDateTime();
        $oneDayInterval = new \DateInterval('P1D');

        do {
            $date = $dateTime->format('Y-m-d');

            if (!$this->isPictureStored($date)) {
                try {
                    $picture = $this->retrievePicture($date);

                    if (null !== $newPicture) {
                        call_user_func($newPicture, $picture);
                    }
                } catch (\Exception $exception) {
                    if (null !== $errorPicture) {
                        call_user_func($errorPicture, $exception);
                    }
                }
            }

            $dateTime->sub($oneDayInterval);
        } while (strcmp($this->options['start_date'], $date) < 1);
    }

    /**
     * {@inheritDoc}
     */
    public function clearStore()
    {
        array_map('unlink', glob($this->options['store_path'].'*.{json,jpg}', GLOB_BRACE));
    }

    /**
     * Retrieve a picture from the API
     * 
     * @param string $date
     * 
     * @return array
     * 
     * @throws \RuntimeException On failing to retrieve picture
     */
    private function retrievePicture($date)
    {
        $response = $this->client->request('GET', $this->options['endpoint'], [
            'query' => [
                'api_key'      => $this->options['api_key'],
                'date'         => $date,
                'concept_tags' => 'true',
            ]
        ]);

        $picture = json_decode((string) $response->getBody(), true);

        if (null === $picture) {
            throw new \RuntimeException('Could not decode response as JSON');
        } elseif (isset($picture['error'])) {
            throw new \RuntimeException($picture['error']);
        }

        $paths = $this->getPicturePaths($date);
        file_put_contents($paths['json_path'], (string) $response->getBody());

        if ('image' === $picture['media_type']) {
            $image = ImageManagerStatic::make($picture['url']);
            $image->fit($this->options['thumbnail_width'], $this->options['thumbnail_height']);
            $image->save($paths['thumbnail_path']);

            $picture['thumbnail_url'] = $paths['thumbnail_url'];
        }

        return $picture;
    }

    /**
     * Get stored picture
     * 
     * @return array
     */
    private function getStoredPicture($date)
    {
        $paths = $this->getPicturePaths($date);
        $picture = json_decode(file_get_contents($paths['json_path']), true);

        if (file_exists($paths['thumbnail_path'])) {
            $picture['thumbnail_url'] = $paths['thumbnail_url'];
        }

        return $picture;
    }

    /**
     * Is a picture stored?
     * 
     * @return bool
     */
    private function isPictureStored($date)
    {
        $jsonPath = $this->getPicturePaths($date)['json_path'];
        return file_exists($jsonPath);
    }

    /**
     * Retrieve the paths for a picture
     * 
     * @return array
     */
    private function getPicturePaths($date)
    {
        $basePath    = $this->options['store_path'].$date;
        $baseUrl     = $this->options['base_url'].$date;
        $thumbSuffix = '.thumb.jpg';

        return [
            'json_path'      => $basePath.'.json',
            'thumbnail_path' => $basePath.$thumbSuffix,
            'thumbnail_url'  => $baseUrl.$thumbSuffix,
        ];
    }

    /**
     * Retrieve the current date time object with the timezone set
     * 
     * @return \DateTime
     */
    private function getDateTime()
    {
        $timezone = new \DateTimeZone($this->options['timezone']);
        $dateTime = new \DateTime('now');
        $dateTime->setTimezone($timezone);
        return $dateTime;
    }
}
