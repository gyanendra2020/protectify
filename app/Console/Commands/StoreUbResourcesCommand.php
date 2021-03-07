<?php

namespace App\Console\Commands;

use Exception;
use App\Models\Ub\UbEvent;
use Illuminate\Support\Str;
use App\Models\Ub\UbResource;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use GuzzleHttp\Exception\ClientException;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Exception\RequestException;

class StoreUbResourcesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ub:store';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Store Ub Resources';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $startedAt = time();

        while (true) {
            $this->extractChildResources();
            $this->downloadResources();
            $this->replaceChildResourceUrls();
            $this->generateResourcesForEvents();

            if (time() - $startedAt > 120) {
                break;
            }

            sleep(1);
        }

        return 0;
    }

    public function extractChildResources()
    {
        $ubResourceQuery = UbResource::query();
        $ubResourceQuery->where('path', '!=', null);
        $ubResourceQuery->where('child_resource_ids', null);
        $ubResourceQuery->with(['page']);

        $ubResourceQuery->chunkById(100, function ($ubResources) {
            foreach ($ubResources as $ubResource) {
                $ubResourcePathParts = explode('/', $ubResource->path);

                if ($ubResourcePathParts[0] === 'db:events') {
                    $ubEvent = UbEvent::find($ubResourcePathParts[1]);
                    $ubResourceContent = $ubEvent->data[$ubResourcePathParts[2]];
                } else {
                    $ubResourceContent = Storage::disk('public')->get('ub-pages/' . $ubResource->page->storage_path . $ubResource->path);
                }

                if ($ubResource->mime === 'text/html') {
                    $childUbResourceUrls = get_visual_resource_urls_from_html($ubResourceContent);
                } elseif ($ubResource->mime === 'text/css') {
                    $childUbResourceUrls = get_visual_resource_urls_from_css($ubResourceContent);
                }

                DB::beginTransaction();

                try {
                    $childUnResources = collect();

                    foreach ($childUbResourceUrls as $childUbResourceUrl) {
                        $childUbResourceHash = md5($childUbResourceUrl);
                        $ubResourceQuery = UbResource::query();
                        $ubResourceQuery->where('page_id', $ubResource->page->id);
                        $ubResourceQuery->where('hash', $childUbResourceHash);
                        $childUbResource = $ubResourceQuery->first();

                        if (!$childUbResource) {
                            $childUbResource = new UbResource;
                            $childUbResource->page_id = $ubResource->page->id;
                            $childUbResource->parent_resource_id = $ubResource->id;
                            $childUbResource->url = $childUbResourceUrl;
                            $childUbResource->hash = $childUbResourceHash;
                            $childUbResource->save();
                        }

                        $childUnResources->push($childUbResource);
                    }

                    $ubResource->child_resource_ids = $childUnResources->pluck('id')->toArray();
                    $ubResource->save();
                } catch (Exception $exception) {
                    DB::rollBack();
                    throw $exception;
                }

                DB::commit();
            }
        });
    }

    public function getFileExtension($mime, $url)
    {
        $mimeMap = [
            'video/3gpp2'                                                               => '3g2',
            'video/3gp'                                                                 => '3gp',
            'video/3gpp'                                                                => '3gp',
            'application/x-compressed'                                                  => '7zip',
            'audio/x-acc'                                                               => 'aac',
            'audio/ac3'                                                                 => 'ac3',
            'application/postscript'                                                    => 'ai',
            'audio/x-aiff'                                                              => 'aif',
            'audio/aiff'                                                                => 'aif',
            'audio/x-au'                                                                => 'au',
            'video/x-msvideo'                                                           => 'avi',
            'video/msvideo'                                                             => 'avi',
            'video/avi'                                                                 => 'avi',
            'application/x-troff-msvideo'                                               => 'avi',
            'application/macbinary'                                                     => 'bin',
            'application/mac-binary'                                                    => 'bin',
            'application/x-binary'                                                      => 'bin',
            'application/x-macbinary'                                                   => 'bin',
            'image/bmp'                                                                 => 'bmp',
            'image/x-bmp'                                                               => 'bmp',
            'image/x-bitmap'                                                            => 'bmp',
            'image/x-xbitmap'                                                           => 'bmp',
            'image/x-win-bitmap'                                                        => 'bmp',
            'image/x-windows-bmp'                                                       => 'bmp',
            'image/ms-bmp'                                                              => 'bmp',
            'image/x-ms-bmp'                                                            => 'bmp',
            'application/bmp'                                                           => 'bmp',
            'application/x-bmp'                                                         => 'bmp',
            'application/x-win-bitmap'                                                  => 'bmp',
            'application/cdr'                                                           => 'cdr',
            'application/coreldraw'                                                     => 'cdr',
            'application/x-cdr'                                                         => 'cdr',
            'application/x-coreldraw'                                                   => 'cdr',
            'image/cdr'                                                                 => 'cdr',
            'image/x-cdr'                                                               => 'cdr',
            'zz-application/zz-winassoc-cdr'                                            => 'cdr',
            'application/mac-compactpro'                                                => 'cpt',
            'application/pkix-crl'                                                      => 'crl',
            'application/pkcs-crl'                                                      => 'crl',
            'application/x-x509-ca-cert'                                                => 'crt',
            'application/pkix-cert'                                                     => 'crt',
            'text/css'                                                                  => 'css',
            'text/x-comma-separated-values'                                             => 'csv',
            'text/comma-separated-values'                                               => 'csv',
            'application/vnd.msexcel'                                                   => 'csv',
            'application/x-director'                                                    => 'dcr',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document'   => 'docx',
            'application/x-dvi'                                                         => 'dvi',
            'message/rfc822'                                                            => 'eml',
            'application/x-msdownload'                                                  => 'exe',
            'video/x-f4v'                                                               => 'f4v',
            'audio/x-flac'                                                              => 'flac',
            'video/x-flv'                                                               => 'flv',
            'image/gif'                                                                 => 'gif',
            'application/gpg-keys'                                                      => 'gpg',
            'application/x-gtar'                                                        => 'gtar',
            'application/x-gzip'                                                        => 'gzip',
            'application/mac-binhex40'                                                  => 'hqx',
            'application/mac-binhex'                                                    => 'hqx',
            'application/x-binhex40'                                                    => 'hqx',
            'application/x-mac-binhex40'                                                => 'hqx',
            'text/html'                                                                 => 'html',
            'image/x-icon'                                                              => 'ico',
            'image/x-ico'                                                               => 'ico',
            'image/vnd.microsoft.icon'                                                  => 'ico',
            'text/calendar'                                                             => 'ics',
            'application/java-archive'                                                  => 'jar',
            'application/x-java-application'                                            => 'jar',
            'application/x-jar'                                                         => 'jar',
            'image/jp2'                                                                 => 'jp2',
            'video/mj2'                                                                 => 'jp2',
            'image/jpx'                                                                 => 'jp2',
            'image/jpm'                                                                 => 'jp2',
            'image/jpg'                                                                 => 'jpg',
            'image/jpeg'                                                                => 'jpg',
            'image/pjpeg'                                                               => 'jpg',
            'application/x-javascript'                                                  => 'js',
            'application/json'                                                          => 'json',
            'text/json'                                                                 => 'json',
            'application/vnd.google-earth.kml+xml'                                      => 'kml',
            'application/vnd.google-earth.kmz'                                          => 'kmz',
            'text/x-log'                                                                => 'log',
            'audio/x-m4a'                                                               => 'm4a',
            'audio/mp4'                                                                 => 'm4a',
            'application/vnd.mpegurl'                                                   => 'm4u',
            'audio/midi'                                                                => 'mid',
            'application/vnd.mif'                                                       => 'mif',
            'video/quicktime'                                                           => 'mov',
            'video/x-sgi-movie'                                                         => 'movie',
            'audio/mpeg'                                                                => 'mp3',
            'audio/mpg'                                                                 => 'mp3',
            'audio/mpeg3'                                                               => 'mp3',
            'audio/mp3'                                                                 => 'mp3',
            'video/mp4'                                                                 => 'mp4',
            'video/mpeg'                                                                => 'mpeg',
            'application/oda'                                                           => 'oda',
            'audio/ogg'                                                                 => 'ogg',
            'video/ogg'                                                                 => 'ogg',
            'application/ogg'                                                           => 'ogg',
            'font/otf'                                                                  => 'otf',
            'application/x-pkcs10'                                                      => 'p10',
            'application/pkcs10'                                                        => 'p10',
            'application/x-pkcs12'                                                      => 'p12',
            'application/x-pkcs7-signature'                                             => 'p7a',
            'application/pkcs7-mime'                                                    => 'p7c',
            'application/x-pkcs7-mime'                                                  => 'p7c',
            'application/x-pkcs7-certreqresp'                                           => 'p7r',
            'application/pkcs7-signature'                                               => 'p7s',
            'application/pdf'                                                           => 'pdf',
            'application/octet-stream'                                                  => 'pdf',
            'application/x-x509-user-cert'                                              => 'pem',
            'application/x-pem-file'                                                    => 'pem',
            'application/pgp'                                                           => 'pgp',
            'application/x-httpd-php'                                                   => 'php',
            'application/php'                                                           => 'php',
            'application/x-php'                                                         => 'php',
            'text/php'                                                                  => 'php',
            'text/x-php'                                                                => 'php',
            'application/x-httpd-php-source'                                            => 'php',
            'image/png'                                                                 => 'png',
            'image/x-png'                                                               => 'png',
            'application/powerpoint'                                                    => 'ppt',
            'application/vnd.ms-powerpoint'                                             => 'ppt',
            'application/vnd.ms-office'                                                 => 'ppt',
            'application/msword'                                                        => 'doc',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'application/x-photoshop'                                                   => 'psd',
            'image/vnd.adobe.photoshop'                                                 => 'psd',
            'audio/x-realaudio'                                                         => 'ra',
            'audio/x-pn-realaudio'                                                      => 'ram',
            'application/x-rar'                                                         => 'rar',
            'application/rar'                                                           => 'rar',
            'application/x-rar-compressed'                                              => 'rar',
            'audio/x-pn-realaudio-plugin'                                               => 'rpm',
            'application/x-pkcs7'                                                       => 'rsa',
            'text/rtf'                                                                  => 'rtf',
            'text/richtext'                                                             => 'rtx',
            'video/vnd.rn-realvideo'                                                    => 'rv',
            'application/x-stuffit'                                                     => 'sit',
            'application/smil'                                                          => 'smil',
            'text/srt'                                                                  => 'srt',
            'image/svg+xml'                                                             => 'svg',
            'application/x-shockwave-flash'                                             => 'swf',
            'application/x-tar'                                                         => 'tar',
            'application/x-gzip-compressed'                                             => 'tgz',
            'image/tiff'                                                                => 'tiff',
            'font/ttf'                                                                  => 'ttf',
            'text/plain'                                                                => 'txt',
            'text/x-vcard'                                                              => 'vcf',
            'application/videolan'                                                      => 'vlc',
            'text/vtt'                                                                  => 'vtt',
            'audio/x-wav'                                                               => 'wav',
            'audio/wave'                                                                => 'wav',
            'audio/wav'                                                                 => 'wav',
            'application/wbxml'                                                         => 'wbxml',
            'video/webm'                                                                => 'webm',
            'image/webp'                                                                => 'webp',
            'audio/x-ms-wma'                                                            => 'wma',
            'application/wmlc'                                                          => 'wmlc',
            'video/x-ms-wmv'                                                            => 'wmv',
            'video/x-ms-asf'                                                            => 'wmv',
            'font/woff'                                                                 => 'woff',
            'font/woff2'                                                                => 'woff2',
            'application/xhtml+xml'                                                     => 'xhtml',
            'application/excel'                                                         => 'xl',
            'application/msexcel'                                                       => 'xls',
            'application/x-msexcel'                                                     => 'xls',
            'application/x-ms-excel'                                                    => 'xls',
            'application/x-excel'                                                       => 'xls',
            'application/x-dos_ms_excel'                                                => 'xls',
            'application/xls'                                                           => 'xls',
            'application/x-xls'                                                         => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'         => 'xlsx',
            'application/vnd.ms-excel'                                                  => 'xlsx',
            'application/xml'                                                           => 'xml',
            'text/xml'                                                                  => 'xml',
            'text/xsl'                                                                  => 'xsl',
            'application/xspf+xml'                                                      => 'xspf',
            'application/x-compress'                                                    => 'z',
            'application/x-zip'                                                         => 'zip',
            'application/zip'                                                           => 'zip',
            'application/x-zip-compressed'                                              => 'zip',
            'application/s-compressed'                                                  => 'zip',
            'multipart/x-zip'                                                           => 'zip',
            'text/x-scriptzsh'                                                          => 'zsh',
        ];

        return $mimeMap[$mime] ?? (pathinfo($url, PATHINFO_EXTENSION) ?: null);
    }

    public function downloadResources()
    {
        $ubResourceQuery = UbResource::query();
        $ubResourceQuery->where('status', null);
        $ubResourceQuery->with(['page', 'parent_resource']);

        $ubResourceQuery->chunkById(100, function ($ubResources) {
            foreach ($ubResources as $ubResource) {
                try {
                    echo "Downloading resource from {$ubResource->absolute_url}\n";
                    $tempFilePath = tempnam('/tmp', 'ub-resource-');
                    $client = new \GuzzleHttp\Client();

                    $response = $client->request('GET', $ubResource->absolute_url, [
                        'connect_timeout' => 20,
                        'sink' => $tempFilePath,
                    ]);

                    $ubResource->mime = Str::before($response->getHeaderLine('Content-Type'), ';');
                    $tempFileExtension = $this->getFileExtension($ubResource->mime, $ubResource->url);
                    $ubResource->path = 'assets/' . $ubResource->hash . '.' . $tempFileExtension;
                    Storage::disk('public')->put('ub-pages/' . $ubResource->page->storage_path . $ubResource->path, File::get($tempFilePath));
                    File::delete($tempFilePath);
                    $ubResource->child_resource_ids = in_array($ubResource->mime, ['text/css']) ? null : [];
                    $ubResource->status = 200;
                    $ubResource->completed_at = in_array($ubResource->mime, ['text/css']) ? null : now();
                    $ubResource->save();
                    echo "Saved ub-pages/{$ubResource->page->storage_path}{$ubResource->path}\n";
                } catch (RequestException $exception) {
                    ++$ubResource->retries_count;
                    echo "Bad request. Total retries: {$ubResource->retries_count}\n";

                    if ($ubResource->retries_count >= 3) {
                        $ubResource->status = 500;
                    }

                    $ubResource->save();
                    continue;
                } catch (ClientException $exception) {
                    if ($exception->getResponse()->getStatusCode() === 404) {
                        $ubResource->status = 404;
                        $ubResource->save();
                        continue;
                    }

                    echo "Status code: " . $exception->getResponse()->getStatusCode() . "\n";
                    throw $exception;
                } catch (ConnectException $exception) {
                    ++$ubResource->retries_count;
                    echo "Can't connect to the host. Total retries: {$ubResource->retries_count}\n";

                    if ($ubResource->retries_count >= 3) {
                        $ubResource->status = 500;
                    }

                    $ubResource->save();
                    continue;
                } catch (Exception $exception) {
                    throw $exception;
                }
            }
        });
    }

    public function replaceChildResourceUrls()
    {
        $ubResourceQuery = UbResource::query();
        $ubResourceQuery->where('child_resource_ids', '!=', null);
        $ubResourceQuery->where('status', 200);
        $ubResourceQuery->whereIn('mime', ['text/html', 'text/css']);
        $ubResourceQuery->where('completed_at', null);
        $ubResourceQuery->orderBy('id', 'asc');
        $ubResourceQuery->with(['page']);

        $ubResourceQuery->chunkById(100, function ($ubResources) {
            foreach ($ubResources as $ubResource) {
                $ubResourceQuery = UbResource::query();
                $ubResourceQuery->whereIn('id', $ubResource->child_resource_ids);
                $ubResourceQuery->where('status', 200);
                $childUbResources = $ubResourceQuery->get();

                if ($childUbResources->where('completed_at', null)->first()) {
                    echo "There is a non-completed child resource...\n";
                    // dd($nonCompletedChildUbResource);
                    continue;
                }

                foreach ($childUbResources as $childUbResource) {
                    $childUbResource->setRelation('parent_resource', $ubResource);
                }

                DB::beginTransaction();

                try {
                    $urlReplacements = $childUbResources->map(function ($childUbResource) use ($ubResource) {
                        return [
                            'old' => $childUbResource->url,

                            'new' => $childUbResource->status === 200
                                ? '/storage/ub-pages/' . $ubResource->page->storage_path . $childUbResource->path
                                : $childUbResource->absolute_url,
                        ];
                    })->filter(function ($replacement) {
                        return $replacement['new'] !== $replacement['old'];
                    })->values()->toArray();

                    if (count($urlReplacements) > 0) {
                        $ubResourcePathParts = explode('/', $ubResource->path);

                        if ($ubResourcePathParts[0] === 'db:events') {
                            $ubEvent = UbEvent::find($ubResourcePathParts[1]);
                            $ubResourceContent = $ubEvent->data[$ubResourcePathParts[2]];
                            echo $ubResource->path . "\n";
                        } else {
                            $ubResourceContent = Storage::disk('public')->get('ub-pages/' . $ubResource->page->storage_path . $ubResource->path);
                            echo 'ub-pages/' . $ubResource->page->storage_path . $ubResource->path . "\n";
                        }

                        if ($ubResource->mime === 'text/html') {
                            $ubResourceContent = replace_resource_urls_in_html($ubResourceContent, $urlReplacements);
                        } else if ($ubResource->mime === 'text/css') {
                            $ubResourceContent = replace_resource_urls_in_css($ubResourceContent, $urlReplacements);
                        }

                        if ($ubResourcePathParts[0] === 'db:events') {
                            $ubEvent->data = array_merge($ubEvent->data, [$ubResourcePathParts[2] => $ubResourceContent]);
                            $ubEvent->save();
                        } else {
                            Storage::disk('public')->put('ub-pages/' . $ubResource->page->storage_path . $ubResource->path, $ubResourceContent);
                        }
                    }

                    $ubResource->completed_at = now();
                    $ubResource->save();
                } catch (Exception $exception) {
                    DB::rollBack();
                    throw $exception;
                }

                DB::commit();
            }
        });
    }

    public function generateResourcesForEvents()
    {
        $ubEventQuery = UbEvent::query();

        $ubEventQuery->where(function ($where) {
            $where->orWhere(function ($where) {
                $where->where('type', 'attribute');
                $where->where('name', 'style');
            });

            $where->orWhere('type', 'add');
        });

        $ubEventQuery->where('resource_ids', null);
        $ubEventQuery->with(['page']);

        $ubEventQuery->chunkById(100, function ($ubEvents) {
            foreach ($ubEvents as $ubEvent) {
                if ($ubEvent->type === 'attribute' && $ubEvent->name === 'style') {
                    DB::beginTransaction();

                    try {
                        $ubResources = collect();

                        if ($ubEvent->data && $ubEvent->data['oldValue']) {
                            $ubResource = new UbResource;
                            $ubResource->page_id = $ubEvent->page->id;
                            $ubResource->url = $ubEvent->page->url . '#event-' . $ubEvent->id . '-old-value';
                            $ubResource->hash = md5($ubResource->url);
                            $ubResource->mime = 'text/css';
                            $ubResource->path = 'db:events/' . $ubEvent->id . '/oldValue';
                            $ubResource->status = 200;
                            $ubResource->save();
                            $ubResources->push($ubResource);
                        }

                        if ($ubEvent->data && $ubEvent->data['newValue']) {
                            $ubResource = new UbResource;
                            $ubResource->page_id = $ubEvent->page->id;
                            $ubResource->url = $ubEvent->page->url . '#event-' . $ubEvent->id . '-new-value';
                            $ubResource->hash = md5($ubResource->url);
                            $ubResource->mime = 'text/css';
                            $ubResource->path = 'db:events/' . $ubEvent->id . '/newValue';
                            $ubResource->status = 200;
                            $ubResource->save();
                            $ubResources->push($ubResource);
                        }

                        $ubEvent->resource_ids = $ubResources->toArray();
                        $ubEvent->save();
                    } catch (Exception $exception) {
                        DB::rollBack();
                        throw $exception;
                    }

                    DB::commit();
                } elseif (in_array($ubEvent->type, ['add', 'remove'])) {
                    DB::beginTransaction();

                    try {
                        if ($ubEvent->data && $ubEvent->data['value']) {
                            $ubResource = new UbResource;
                            $ubResource->page_id = $ubEvent->page->id;
                            $ubResource->url = $ubEvent->page->url . '#event-' . $ubEvent->id . '-value';
                            $ubResource->hash = md5($ubResource->url);
                            $ubResource->mime = 'text/html';
                            $ubResource->path = 'db:events/' . $ubEvent->id . '/value';
                            $ubResource->status = 200;
                            $ubResource->save();

                            $ubEvent->resource_ids = [$ubResource->id];
                        } else {
                            $ubEvent->resource_ids = [];
                        }

                        $ubEvent->save();
                    } catch (Exception $exception) {
                        DB::rollBack();
                        throw $exception;
                    }

                    DB::commit();
                }
            }
        });
    }
}
