#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Скрипт для генерации PDF регламентов из Markdown файлов
Требует установки: pip install markdown weasyprint
"""

import markdown
from weasyprint import HTML, CSS
from weasyprint.text.fonts import FontConfiguration
import os

def get_css_styles():
    """Возвращает CSS стили для PDF"""
    return """
    @page {
        size: A4;
        margin: 2.5cm 2cm;

        @bottom-center {
            content: "© EXORD 2024";
            font-size: 9pt;
            color: #666;
        }

        @bottom-right {
            content: "Стр. " counter(page) " из " counter(pages);
            font-size: 9pt;
            color: #666;
        }
    }

    body {
        font-family: "DejaVu Sans", Arial, sans-serif;
        font-size: 11pt;
        line-height: 1.6;
        color: #333;
        text-align: justify;
    }

    h1 {
        font-size: 22pt;
        color: #1a1a1a;
        text-align: center;
        margin: 20px 0;
        padding: 15px 0;
        border-bottom: 3px solid #4CAF50;
        page-break-after: avoid;
    }

    h2 {
        font-size: 16pt;
        color: #2c3e50;
        margin-top: 25px;
        margin-bottom: 15px;
        padding: 10px 0 10px 10px;
        border-bottom: 2px solid #e0e0e0;
        background-color: #f8f9fa;
        page-break-after: avoid;
    }

    h3 {
        font-size: 13pt;
        color: #34495e;
        margin-top: 20px;
        margin-bottom: 10px;
        font-weight: bold;
        page-break-after: avoid;
    }

    h4 {
        font-size: 12pt;
        color: #555;
        margin-top: 15px;
        margin-bottom: 8px;
        font-weight: bold;
        page-break-after: avoid;
    }

    p {
        margin: 8px 0;
    }

    strong {
        color: #d32f2f;
        font-weight: bold;
    }

    em {
        font-style: italic;
        color: #555;
    }

    ul, ol {
        margin: 10px 0 10px 20px;
        padding: 0;
    }

    li {
        margin: 5px 0;
        line-height: 1.5;
    }

    hr {
        border: none;
        border-top: 1px solid #ddd;
        margin: 20px 0;
    }

    code {
        background-color: #f5f5f5;
        padding: 2px 6px;
        border-radius: 3px;
        font-family: "Courier New", monospace;
        font-size: 10pt;
    }

    pre {
        background-color: #f5f5f5;
        padding: 10px;
        border-radius: 5px;
        border-left: 3px solid #4CAF50;
        overflow-x: auto;
    }

    blockquote {
        border-left: 4px solid #4CAF50;
        padding-left: 15px;
        margin: 15px 0;
        color: #555;
        font-style: italic;
    }

    table {
        width: 100%;
        border-collapse: collapse;
        margin: 15px 0;
    }

    th, td {
        border: 1px solid #ddd;
        padding: 8px;
        text-align: left;
    }

    th {
        background-color: #4CAF50;
        color: white;
        font-weight: bold;
    }

    /* Избегаем разрыва страницы внутри элементов */
    h1, h2, h3, h4, h5, h6 {
        page-break-inside: avoid;
    }

    ul, ol, p {
        page-break-inside: avoid;
        orphans: 3;
        widows: 3;
    }
    """

def markdown_to_pdf(md_file, pdf_file, title):
    """
    Конвертирует Markdown файл в PDF

    Args:
        md_file: путь к исходному Markdown файлу
        pdf_file: путь к выходному PDF файлу
        title: заголовок документа
    """
    try:
        # Читаем markdown файл
        with open(md_file, 'r', encoding='utf-8') as f:
            md_content = f.read()

        # Конвертируем markdown в HTML
        html_content = markdown.markdown(
            md_content,
            extensions=[
                'extra',
                'nl2br',
                'sane_lists',
                'smarty',
                'toc'
            ]
        )

        # Создаем полный HTML документ
        full_html = f"""
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="UTF-8">
            <title>{title}</title>
        </head>
        <body>
            {html_content}
        </body>
        </html>
        """

        # Создаем PDF
        font_config = FontConfiguration()
        html = HTML(string=full_html)
        css = CSS(string=get_css_styles(), font_config=font_config)

        html.write_pdf(pdf_file, stylesheets=[css], font_config=font_config)

        print(f"✓ Создан: {pdf_file}")
        return True

    except Exception as e:
        print(f"✗ Ошибка при создании {pdf_file}: {str(e)}")
        return False

def main():
    """Главная функция"""
    print("=" * 60)
    print("Генерация PDF регламентов")
    print("=" * 60)
    print()

    # Путь к папке с документами
    docs_dir = os.path.dirname(os.path.abspath(__file__))

    # Генерируем русскую версию
    print("1. Генерация русской версии...")
    ru_md = os.path.join(docs_dir, "writeoff-regulation-ru.md")
    ru_pdf = os.path.join(docs_dir, "writeoff-regulation-ru.pdf")
    ru_success = markdown_to_pdf(
        ru_md,
        ru_pdf,
        "Регламент работы с функционалом списания продуктов"
    )
    print()

    # Генерируем узбекскую версию
    print("2. Генерация узбекской версии...")
    uz_md = os.path.join(docs_dir, "writeoff-regulation-uz.md")
    uz_pdf = os.path.join(docs_dir, "writeoff-regulation-uz.pdf")
    uz_success = markdown_to_pdf(
        uz_md,
        uz_pdf,
        "Mahsulotlarni hisobdan chiqarish funksiyasi bilan ishlash tartibi"
    )
    print()

    # Итоги
    print("=" * 60)
    if ru_success and uz_success:
        print("✓ Все PDF файлы успешно созданы!")
        print(f"  - {os.path.basename(ru_pdf)}")
        print(f"  - {os.path.basename(uz_pdf)}")
    else:
        print("✗ Не все файлы были созданы. Проверьте ошибки выше.")
    print("=" * 60)

if __name__ == "__main__":
    main()
