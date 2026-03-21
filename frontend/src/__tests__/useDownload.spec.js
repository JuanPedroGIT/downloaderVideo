import { describe, it, expect, beforeEach, vi, afterEach } from 'vitest'
import { useDownload } from '../composables/useDownload.js'

describe('useDownload – validateUrl', () => {
  let download

  beforeEach(() => {
    download = useDownload()
  })

  afterEach(() => {
    vi.restoreAllMocks()
  })

  it('returns false and sets urlError when url is empty', () => {
    const result = download.validateUrl('')
    expect(result).toBe(false)
    expect(download.urlError.value).toBeTruthy()
  })

  it('returns false for a non-URL string', () => {
    const result = download.validateUrl('not-a-url')
    expect(result).toBe(false)
    expect(download.urlError.value).toMatch(/invalid/i)
  })

  it('returns false for a non-YouTube domain', () => {
    const result = download.validateUrl('https://vimeo.com/123')
    expect(result).toBe(false)
    expect(download.urlError.value).toMatch(/unsupported/i)
  })

  it('returns true for a valid YouTube URL', () => {
    const result = download.validateUrl('https://www.youtube.com/watch?v=dQw4w9WgXcQ')
    expect(result).toBe(true)
    expect(download.urlError.value).toBe('')
  })

  it('returns true for a youtu.be short URL', () => {
    const result = download.validateUrl('https://youtu.be/dQw4w9WgXcQ')
    expect(result).toBe(true)
  })
})

describe('useDownload – clearStatus', () => {
  it('resets all state to defaults', () => {
    const download = useDownload()
    download.status.value     = 'some message'
    download.statusType.value = 'error'
    download.progress.value   = 50
    download.urlError.value   = 'bad url'

    download.clearStatus()

    expect(download.status.value).toBe('')
    expect(download.statusType.value).toBe('')
    expect(download.progress.value).toBe(0)
    expect(download.urlError.value).toBe('')
  })
})

describe('useDownload – startDownload', () => {
  it('sets error status when fetch fails', async () => {
    const download = useDownload()
    vi.stubGlobal('fetch', vi.fn().mockRejectedValue(new Error('Network error')))

    await download.startDownload('https://www.youtube.com/watch?v=abc', 'mp3')

    expect(download.isLoading.value).toBe(false)
    expect(download.statusType.value).toBe('error')
    expect(download.status.value).toMatch(/network error/i)
  })

  it('sets error status when server returns non-ok response', async () => {
    const download = useDownload()
    vi.stubGlobal('fetch', vi.fn().mockResolvedValue({ ok: false }))

    await download.startDownload('https://www.youtube.com/watch?v=abc', 'mp3')

    expect(download.isLoading.value).toBe(false)
    expect(download.statusType.value).toBe('error')
  })

  it('does nothing when URL is invalid', async () => {
    const download = useDownload()
    const fetchMock = vi.fn()
    vi.stubGlobal('fetch', fetchMock)

    await download.startDownload('not-a-url', 'mp3')

    expect(fetchMock).not.toHaveBeenCalled()
    expect(download.isLoading.value).toBe(false)
  })
})
