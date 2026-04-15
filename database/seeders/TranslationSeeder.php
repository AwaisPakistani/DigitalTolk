<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\{Tag, Translation};
class TranslationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Create tags
        $tags = [
            ['name' => 'Mobile', 'slug' => 'mobile', 'description' => 'Mobile app translations'],
            ['name' => 'Desktop', 'slug' => 'desktop', 'description' => 'Desktop app translations'],
            ['name' => 'Web', 'slug' => 'web', 'description' => 'Web app translations'],
        ];

        foreach ($tags as $tag) {
            Tag::create($tag);
        }

        // Sample translations
        $translations = [
            ['key' => 'welcome_message', 'locale' => 'en', 'value' => 'Welcome to our application!', 'group' => 'general'],
            ['key' => 'welcome_message', 'locale' => 'fr', 'value' => 'Bienvenue dans notre application!', 'group' => 'general'],
            ['key' => 'welcome_message', 'locale' => 'es', 'value' => '¡Bienvenido a nuestra aplicación!', 'group' => 'general'],
            ['key' => 'login_button', 'locale' => 'en', 'value' => 'Login', 'group' => 'auth'],
            ['key' => 'login_button', 'locale' => 'fr', 'value' => 'Connexion', 'group' => 'auth'],
            ['key' => 'login_button', 'locale' => 'es', 'value' => 'Iniciar sesión', 'group' => 'auth'],
        ];

        foreach ($translations as $translationData) {
            Translation::create($translationData);
        }
    }
}
