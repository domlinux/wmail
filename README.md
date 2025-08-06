# Laravel 自訂電子郵件發送工具

這是一個基於 Laravel 框架開發的應用程式，旨在提供一個手動設定和發送電子郵件的介面，支援多種進階功能，包括自訂字元集、編碼方式以及附件處理。

## 功能特色

*   **手動電子郵件配置**：使用者可以自訂收件人、主旨和內容。
*   **多格式內容支援**：支援純文字、HTML 或同時包含兩種格式的電子郵件內容。
*   **自訂字元集**：支援 UTF-8、GBK、Big5 等多種字元集編碼，確保郵件內容和附件檔名在不同語系環境下正確顯示。
*   **多種編碼方式**：支援 7bit、8bit、Base64 和 Quoted-Printable 等內容傳輸編碼方式。
*   **附件功能**：允許使用者上傳並附加檔案到電子郵件中。
*   **設定持久化**：自動保存最後一次發送郵件的設定（收件人、主旨、內容、內容類型、字元集、編碼），以便下次開啟應用程式時自動載入。

## 技術棧

*   **後端框架**：Laravel
*   **郵件傳輸**：PHPMailer (作為 Laravel 的自訂郵件傳輸器)

## 開發過程與注意事項

在開發此專案的過程中，我們解決了多個關鍵挑戰，以下是主要的問題點及解決方案：

1.  **PHPMailer 整合**：
    *   將 PHPMailer 作為 Laravel 的自訂郵件傳輸器進行整合，透過 `PHPMailerServiceProvider` 擴展 `Mail` Facade。
    *   配置 `config/mail.php` 以使用 `phpmailer` 傳輸器，並處理 TLSv1.2、客戶端憑證驗證等複雜的郵件伺服器連線要求。

2.  **`UploadedFile` 序列化問題**：
    *   由於 `CustomEmail` Mailable 實作了 `ShouldQueue` 介面，Laravel 會嘗試序列化整個 Mailable 物件。`Illuminate\Http\UploadedFile` 物件不可序列化，導致 `Serialization of 'Illuminate\Http\UploadedFile' is not allowed` 錯誤。
    *   **解決方案**：在 `EmailController` 中，將 `UploadedFile` 物件從傳遞給 Mailable 的 `$data` 中移除，並在 Mailable 外部直接使用 `attach()` 方法，傳遞附件的臨時路徑。

3.  **字元集與編碼處理**：
    *   **主旨亂碼**：主旨需要像郵件內容一樣進行字元集轉換，以避免亂碼。
    *   **內容編碼**：透過自訂 `X-Mailer-Encoding` 和 `X-Mailer-Charset` 標頭，將使用者選擇的編碼和字元集資訊從 `CustomEmail` 傳遞到 `PHPMailerTransport`。
    *   **附件編碼**：PHPMailer 在處理附件編碼時對參數有嚴格要求。
        *   **解決方案**：在 `PHPMailerTransport` 中，根據 `X-Mailer-Encoding` 標頭的值，明確地使用 PHPMailer 內建的編碼常量（如 `PHPMailer::ENCODING_BASE64`）來設定附件的編碼。
    *   **附件檔名亂碼**：當使用非 UTF-8 字元集（如 Big5）時，附件檔名會出現亂碼。
        *   **解決方案**：在 `PHPMailerTransport` 中，將附件檔名從 UTF-8 轉換為目標字元集，然後再傳遞給 PHPMailer。

4.  **使用者設定持久化**：
    *   為了在應用程式重啟後保留使用者最後一次的設定，我們將設定儲存到本地的 JSON 檔案中。
    *   **解決方案**：在 `EmailController@send` 方法中，將相關設定儲存到 `storage/app/email_settings.json`。在 `EmailController@create` 方法中，讀取此檔案作為表單的預設值。
    *   **`old()` 輔助函式行為**：`old()` 函式優先從 `flashInput()` 獲取值，而 `flashInput()` 僅在下一個請求中有效。
        *   **解決方案**：確保 `resources/views/email/create.blade.php` 中的所有表單欄位都使用 `old('field_name', $defaultSettings['field_name'])` 的形式，這樣即使 `flashInput()` 的資料消失，也能從持久化儲存的設定中獲取預設值。

## 安裝與運行

1.  **克隆專案**：
    ```bash
    git clone [您的專案 Git URL] wmail
    cd wmail
    ```
2.  **安裝 Composer 依賴**：
    ```bash
    composer install
    ```
3.  **複製 `.env` 檔案並生成應用程式金鑰**：
    ```bash
    cp .env.example .env
    php artisan key:generate
    ```
4.  **配置郵件設定**：
    *   編輯 `.env` 檔案，根據您的郵件伺服器配置 `MAIL_MAILER`、`MAIL_HOST`、`MAIL_PORT`、`MAIL_USERNAME`、`MAIL_PASSWORD`、`MAIL_ENCRYPTION` 等。
    *   如果您的郵件伺服器需要客戶端憑證，請確保 `storage/certs/client.pem` 和 `storage/certs/client.key` 存在並配置正確的路徑。
    *   `MAIL_MAILER` 應設定為 `phpmailer`。
5.  **運行應用程式**：
    ```bash
    php artisan serve
    ```
6.  在瀏覽器中訪問 `http://127.0.0.1:8000` (或您配置的位址)。
