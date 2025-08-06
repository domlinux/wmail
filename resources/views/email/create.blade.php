<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Email</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>Create Email</h1>

        @if (session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        <form action="{{ route('email.send') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
                <label for="to" class="form-label">To</label>
                <input type="email" class="form-control" id="to" name="to" value="{{ old('to', $defaultSettings['to']) }}" required>
            </div>
            <div class="mb-3">
                <label for="subject" class="form-label">Subject</label>
                <input type="text" class="form-control" id="subject" name="subject" value="{{ old('subject', $defaultSettings['subject']) }}" required>
            </div>
            <div class="mb-3">
                <label for="content" class="form-label">Content</label>
                <textarea class="form-control" id="content" name="content" rows="5" required>{{ old('content', $defaultSettings['content']) }}</textarea>
            </div>
            <div class="mb-3">
                <label for="attachment" class="form-label">Attachment</label>
                <input type="file" class="form-control" id="attachment" name="attachment">
            </div>
            <div class="mb-3">
                <label for="content_type" class="form-label">Content Type</label>
                <select class="form-select" id="content_type" name="content_type">
                    <option value="text" {{ old('content_type', $defaultSettings['content_type']) == 'text' ? 'selected' : '' }}>Text</option>
                    <option value="html" {{ old('content_type') == 'html' ? 'selected' : '' }}>HTML</option>
                    <option value="both" {{ old('content_type', $defaultSettings['content_type']) == 'both' ? 'selected' : '' }}>Both (HTML and Text)</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="charset" class="form-label">Charset</label>
                <select class="form-select" id="charset" name="charset">
                    <option value="utf-8" {{ old('charset', $defaultSettings['charset']) == 'utf-8' ? 'selected' : '' }}>UTF-8</option>
                    <option value="gbk" {{ old('charset') == 'gbk' ? 'selected' : '' }}>GBK</option>
                    <option value="big5" {{ old('charset') == 'big5' ? 'selected' : '' }}>Big5</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="encoding" class="form-label">Encoding</label>
                <select class="form-select" id="encoding" name="encoding">
                    <option value="7bit" {{ old('encoding', $defaultSettings['encoding']) == '7bit' ? 'selected' : '' }}>7bit</option>
                    <option value="8bit" {{ old('encoding', $defaultSettings['encoding']) == '8bit' ? 'selected' : '' }}>8bit</option>
                    <option value="base64" {{ old('encoding', $defaultSettings['encoding']) == 'base64' ? 'selected' : '' }}>Base64</option>
                    <option value="quoted-printable" {{ old('encoding', $defaultSettings['encoding']) == 'quoted-printable' ? 'selected' : '' }}>Quoted-Printable</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Send Email</button>
        </form>
    </div>
</body>
</html>
