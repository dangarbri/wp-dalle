let prompt = document.getElementById('js-prompt');
let content = document.getElementById('js-content');
let submit = document.getElementById('js-button');
let separator = document.getElementById('js-separator');
let span = document.getElementById('js-span');
let pending = false;

submit.onclick = GenerateImage;

async function GenerateImage() {
    if (pending) { return; }

    BeginLoading();
    try {
        let response = await fetch("https://dangarbri.tech/wp-json/dalle-rest-api/v1/dalle?prompt=" + encodeURIComponent(prompt.value));
        let json = await response.json();
        if (json.hasOwnProperty('error')) {
            ApplyError(json.error.message, prompt.value);
        } else {
            ApplyImage(json, prompt.value);
        }
    } catch (e) {
        ApplyError("Failed to generate image for prompt '" + prompt.value + "'");
        throw e;
    } finally {
        CompleteLoading();
    }
}

function ApplyImage(json, prompt) {
    let img = document.createElement('img');
    img.src = json.data[0].url;
    img.alt = prompt;
    content.prepend(img);

    let p = document.createElement('p');
    p.textContent = "DALL-E's revised prompt: " + json.data[0].revised_prompt;
    content.prepend(p);
}

function ApplyError(error, prompt) {
    let p = document.createElement('p');
    p.textContent = error;
    content.prepend(p);

    if (prompt) {
        p = document.createElement('p');
        p.textContent = "Prompt: " + prompt;
        content.prepend(p);
    }
}

function BeginLoading() {
    pending = true;
    separator.classList.add('image-loading');
}

function CompleteLoading() {
    pending = false;
    separator.classList.remove('image-loading');
    span.remove();
}