<?php

declare(strict_types=1);

namespace App\Tests\Unit\Infrastructure\Provider;

use App\Service\Provider\YouTubeProvider;
use PHPUnit\Framework\TestCase;

class YouTubeProviderTest extends TestCase
{
    private YouTubeProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new YouTubeProvider();
    }

    /** @dataProvider youTubeUrls */
    public function test_supports_all_youtube_domains(string $url): void
    {
        $this->assertTrue($this->provider->supports($url));
    }

    /** @return array<array{string}> */
    public static function youTubeUrls(): array
    {
        return [
            ['https://www.youtube.com/watch?v=dQw4w9WgXcQ'],
            ['https://youtube.com/watch?v=abc'],
            ['https://youtu.be/dQw4w9WgXcQ'],
            ['https://m.youtube.com/watch?v=abc'],
            ['https://music.youtube.com/watch?v=abc'],
        ];
    }

    /** @dataProvider nonYouTubeUrls */
    public function test_rejects_non_youtube_domains(string $url): void
    {
        $this->assertFalse($this->provider->supports($url));
    }

    /** @return array<array{string}> */
    public static function nonYouTubeUrls(): array
    {
        return [
            ['https://vimeo.com/123456'],
            ['https://tiktok.com/@user/video/123'],
            ['https://evil.com/youtube.com/fake'],
            ['https://notyoutube.com/watch?v=abc'],
            ['not-a-url'],
        ];
    }

    public function test_builds_mp3_args(): void
    {
        $args = $this->provider->buildArgs(
            'https://www.youtube.com/watch?v=abc',
            'mp3',
            ['mp3' => '-x --audio-format mp3 --audio-quality 0']
        );

        $this->assertContains('-x', $args);
        $this->assertContains('--audio-format', $args);
        $this->assertContains('mp3', $args);
        $this->assertContains('--', $args);
        $this->assertSame('https://www.youtube.com/watch?v=abc', end($args));
    }

    public function test_builds_mp4_args_with_quoted_format_string(): void
    {
        $args = $this->provider->buildArgs(
            'https://www.youtube.com/watch?v=abc',
            'mp4',
            ['mp4' => '-f "bestvideo[ext=mp4]+bestaudio[ext=m4a]/best[ext=mp4]/best"']
        );

        $this->assertContains('-f', $args);
        $this->assertContains('bestvideo[ext=mp4]+bestaudio[ext=m4a]/best[ext=mp4]/best', $args);
    }

    public function test_url_comes_after_double_dash_separator(): void
    {
        $url  = 'https://youtu.be/test123';
        $args = $this->provider->buildArgs($url, 'mp3', ['mp3' => '-x']);

        $separatorIndex = array_search('--', $args, true);
        $this->assertNotFalse($separatorIndex, '"--" separator not found in args.');
        $this->assertSame($url, $args[$separatorIndex + 1]);
    }

    public function test_double_dash_prevents_url_injection(): void
    {
        // Even a URL that looks like a flag cannot be interpreted as one
        // because "--" marks the end of options in yt-dlp.
        $maliciousUrl = 'https://www.youtube.com/watch?v=abc --extract-audio';
        $args         = $this->provider->buildArgs($maliciousUrl, 'mp4', ['mp4' => '-f best']);

        $separatorIndex = array_search('--', $args, true);
        $this->assertGreaterThan(0, $separatorIndex);
        // URL is passed as a single argument after "--"
        $this->assertSame($maliciousUrl, $args[$separatorIndex + 1]);
    }
}
