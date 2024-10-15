import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";
import typography from "@tailwindcss/typography";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./vendor/laravel/jetstream/**/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./resources/js/**/*.vue",
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ["Inter", ...defaultTheme.fontFamily.sans],
            },
            colors: {
                primary: "#000000",
                secondary: "#FFFFFF",
                accent: "#808080",
                background: "#F5F5F5",
            },
            borderRadius: {
                xl: "1rem",
            },
        },
    },

    plugins: [forms, typography, require("daisyui")],

    daisyui: {
        themes: ["light"],
    },
};
