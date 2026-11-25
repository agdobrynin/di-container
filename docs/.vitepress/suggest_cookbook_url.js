
export default function () {
    const owner = 'agdobrynin';
    const repo = 'di-container';

    const title = encodeURIComponent('Suggestion for: Cookbook section');
    const body = encodeURIComponent('**Describe your suggestion:**\n\n');
    const labels = encodeURIComponent('enhancement,documentation');

    return `https://github.com/${ owner }/${ repo }/issues/new?title=${ title }&body=${ body }&labels=${ labels }`;
}