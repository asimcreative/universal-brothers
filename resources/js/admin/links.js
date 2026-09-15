// Link and video address rules shared by the page builder and the text editor.
// They mirror App\Support\PageBuilder\SectionValidator and VideoEmbed on the
// server, which remain the real check; these give instant feedback.

/** A page on this site, a place on the page, a web address, an email or a phone link. */
export function isValidLink(link) {
    const value = String(link || '').trim();
    if (/^\/(?!\/)[^\s<>"]*$/.test(value) || /^#[A-Za-z][\w-]*$/.test(value)) return true;
    if (/^mailto:[^\s@<>"]+@[^\s@<>"]+\.[^\s@<>"]+$/i.test(value) || /^tel:\+?[0-9 ()-]{5,20}$/i.test(value)) return true;
    try {
        const parsed = new URL(value);
        return ['http:', 'https:'].includes(parsed.protocol) && (parsed.hostname.includes('.') || parsed.hostname === 'localhost');
    } catch {
        return false;
    }
}

export function linkSuggestion(link) {
    const value = String(link || '').trim();
    if (/^www\./i.test(value) || /^[a-z0-9-]+(\.[a-z0-9-]+)+(\/.*)?$/i.test(value)) return `Add https:// at the start, like https://${value}.`;
    if (/^[\w@.+-]+@[\w-]+\.[\w.]+$/.test(value)) return `For an email address, write mailto:${value}.`;
    if (/^[a-z0-9-]+(\/[a-z0-9-]*)*$/i.test(value)) return `For a page on this website, start with a slash, like /${value}.`;
    return 'Use a page on this website starting with a slash (for example /contact) or a full address starting with https://.';
}

/** YouTube (privacy-enhanced domain) or Vimeo player address, or null. */
export function videoEmbedUrl(url) {
    const value = String(url || '').trim();
    const youtube = value.match(/^https?:\/\/(?:www\.|m\.)?youtube\.com\/watch\?(?:.*&)?v=([A-Za-z0-9_-]{6,20})/i)
        || value.match(/^https?:\/\/(?:www\.)?youtu\.be\/([A-Za-z0-9_-]{6,20})/i)
        || value.match(/^https?:\/\/(?:www\.)?youtube(?:-nocookie)?\.com\/(?:embed|shorts|live)\/([A-Za-z0-9_-]{6,20})/i);
    if (youtube) return `https://www.youtube-nocookie.com/embed/${youtube[1]}`;
    const vimeo = value.match(/^https?:\/\/(?:www\.|player\.)?vimeo\.com\/(?:video\/)?(\d{3,12})/i);
    return vimeo ? `https://player.vimeo.com/video/${vimeo[1]}` : null;
}
