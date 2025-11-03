#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Скрипт для генерации PDF регламентов из Markdown файлов
Требует установки: pip install reportlab markdown
"""

import markdown
import os
from reportlab.lib.pagesizes import A4
from reportlab.lib.styles import getSampleStyleSheet, ParagraphStyle
from reportlab.lib.units import cm
from reportlab.platypus import SimpleDocTemplate, Paragraph, Spacer, PageBreak
from reportlab.platypus import Table, TableStyle
from reportlab.lib.enums import TA_CENTER, TA_JUSTIFY, TA_LEFT
from reportlab.lib import colors
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
import re

def setup_fonts():
    """Регистрация шрифтов с поддержкой кириллицы"""
    try:
        # Пытаемся зарегистрировать DejaVu Sans
        font_paths = [
            '/System/Library/Fonts/Supplemental/Arial Unicode.ttf',
            '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf',
            '/Library/Fonts/Arial Unicode.ttf',
        ]

        for font_path in font_paths:
            if os.path.exists(font_path):
                pdfmetrics.registerFont(TTFont('CustomFont', font_path))
                return 'CustomFont'
    except:
        pass

    # Если не удалось зарегистрировать, используем Helvetica
    return 'Helvetica'

def create_styles(font_name):
    """Создание стилей для документа"""
    styles = getSampleStyleSheet()

    # Стиль для заголовка H1
    styles.add(ParagraphStyle(
        name='CustomH1',
        parent=styles['Heading1'],
        fontName=font_name,
        fontSize=22,
        textColor=colors.HexColor('#1a1a1a'),
        spaceAfter=20,
        alignment=TA_CENTER,
        borderWidth=2,
        borderColor=colors.HexColor('#4CAF50'),
        borderPadding=10,
    ))

    # Стиль для заголовка H2
    styles.add(ParagraphStyle(
        name='CustomH2',
        parent=styles['Heading2'],
        fontName=font_name,
        fontSize=16,
        textColor=colors.HexColor('#2c3e50'),
        spaceAfter=12,
        spaceBefore=20,
        backColor=colors.HexColor('#f8f9fa'),
        borderWidth=1,
        borderColor=colors.HexColor('#e0e0e0'),
        borderPadding=8,
    ))

    # Стиль для заголовка H3
    styles.add(ParagraphStyle(
        name='CustomH3',
        parent=styles['Heading3'],
        fontName=font_name,
        fontSize=13,
        textColor=colors.HexColor('#34495e'),
        spaceAfter=10,
        spaceBefore=15,
    ))

    # Стиль для обычного текста
    styles.add(ParagraphStyle(
        name='CustomBody',
        parent=styles['BodyText'],
        fontName=font_name,
        fontSize=11,
        leading=16,
        alignment=TA_JUSTIFY,
        spaceAfter=8,
    ))

    # Стиль для списков
    styles.add(ParagraphStyle(
        name='CustomBullet',
        parent=styles['BodyText'],
        fontName=font_name,
        fontSize=11,
        leading=16,
        leftIndent=20,
        spaceAfter=5,
    ))

    return styles

def parse_markdown_to_elements(md_content, styles):
    """Парсинг markdown и преобразование в элементы reportlab"""
    elements = []
    lines = md_content.split('\n')

    i = 0
    while i < len(lines):
        line = lines[i].strip()

        # Пропускаем пустые строки
        if not line:
            i += 1
            continue

        # Горизонтальная линия
        if line == '---':
            elements.append(Spacer(1, 0.5*cm))
            elements.append(Table([['']],  colWidths=[18*cm],
                                style=[('LINEABOVE', (0,0), (-1,0), 1, colors.grey)]))
            elements.append(Spacer(1, 0.5*cm))
            i += 1
            continue

        # Заголовок H1
        if line.startswith('# '):
            text = line[2:].strip()
            elements.append(Paragraph(text, styles['CustomH1']))
            elements.append(Spacer(1, 0.3*cm))
            i += 1
            continue

        # Заголовок H2
        if line.startswith('## '):
            text = line[3:].strip()
            elements.append(Spacer(1, 0.3*cm))
            elements.append(Paragraph(text, styles['CustomH2']))
            elements.append(Spacer(1, 0.2*cm))
            i += 1
            continue

        # Заголовок H3
        if line.startswith('### '):
            text = line[4:].strip()
            elements.append(Paragraph(text, styles['CustomH3']))
            elements.append(Spacer(1, 0.1*cm))
            i += 1
            continue

        # Список
        if line.startswith('- ') or line.startswith('✓ '):
            bullet = '•' if line.startswith('- ') else '✓'
            text = line[2:].strip()

            # Обрабатываем жирный текст
            text = re.sub(r'\*\*(.*?)\*\*', r'<b>\1</b>', text)
            text = re.sub(r'_(.*?)_', r'<i>\1</i>', text)

            para = Paragraph(f'{bullet} {text}', styles['CustomBullet'])
            elements.append(para)
            i += 1
            continue

        # Обычный текст
        text = line

        # Обрабатываем форматирование
        text = re.sub(r'\*\*(.*?)\*\*', r'<b>\1</b>', text)
        text = re.sub(r'_(.*?)_', r'<i>\1</i>', text)

        # Если строка начинается с жирного текста и двоеточия, это может быть заголовок подраздела
        if text.startswith('<b>') and ':' in text:
            para = Paragraph(text, styles['CustomBody'])
        else:
            para = Paragraph(text, styles['CustomBody'])

        elements.append(para)
        i += 1

    return elements

def footer(canvas, doc):
    """Функция для добавления нижнего колонтитула"""
    canvas.saveState()
    footer_text = "© EXORD 2025"
    page_num = f"Стр. {canvas.getPageNumber()}"

    canvas.setFont('Helvetica', 9)
    canvas.drawCentredString(A4[0] / 2, 1.5*cm, footer_text)
    canvas.drawRightString(A4[0] - 2*cm, 1.5*cm, page_num)
    canvas.restoreState()

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

        # Настраиваем шрифты
        font_name = setup_fonts()

        # Создаем стили
        styles = create_styles(font_name)

        # Создаем PDF документ
        doc = SimpleDocTemplate(
            pdf_file,
            pagesize=A4,
            rightMargin=2*cm,
            leftMargin=2*cm,
            topMargin=2.5*cm,
            bottomMargin=2.5*cm,
            title=title,
            author='EXORD System',
        )

        # Парсим markdown и создаем элементы
        elements = parse_markdown_to_elements(md_content, styles)

        # Генерируем PDF
        doc.build(elements, onFirstPage=footer, onLaterPages=footer)

        print(f"✓ Создан: {os.path.basename(pdf_file)}")
        return True

    except Exception as e:
        print(f"✗ Ошибка при создании {os.path.basename(pdf_file)}: {str(e)}")
        import traceback
        traceback.print_exc()
        return False

def main():
    """Главная функция"""
    print("=" * 60)
    print("Генерация PDF регламентов")
    print("=" * 60)
    print()

    # Путь к папке с документами
    docs_dir = os.path.dirname(os.path.abspath(__file__))

    # Генерируем регламенты списания
    print("РЕГЛАМЕНТЫ СПИСАНИЯ ПРОДУКТОВ")
    print("-" * 60)
    print("1. Генерация русской версии...")
    ru_writeoff_md = os.path.join(docs_dir, "writeoff-regulation-ru.md")
    ru_writeoff_pdf = os.path.join(docs_dir, "writeoff-regulation-ru.pdf")
    ru_writeoff_success = markdown_to_pdf(
        ru_writeoff_md,
        ru_writeoff_pdf,
        "Регламент работы с функционалом списания продуктов"
    )
    print()

    print("2. Генерация узбекской версии...")
    uz_writeoff_md = os.path.join(docs_dir, "writeoff-regulation-uz.md")
    uz_writeoff_pdf = os.path.join(docs_dir, "writeoff-regulation-uz.pdf")
    uz_writeoff_success = markdown_to_pdf(
        uz_writeoff_md,
        uz_writeoff_pdf,
        "Mahsulotlarni hisobdan chiqarish funksiyasi bilan ishlash tartibi"
    )
    print()

    # Генерируем регламенты внутреннего перемещения
    print("РЕГЛАМЕНТЫ ВНУТРЕННЕГО ПЕРЕМЕЩЕНИЯ")
    print("-" * 60)
    print("3. Генерация русской версии...")
    ru_transfer_md = os.path.join(docs_dir, "store-transfer-regulation-ru.md")
    ru_transfer_pdf = os.path.join(docs_dir, "store-transfer-regulation-ru.pdf")
    ru_transfer_success = markdown_to_pdf(
        ru_transfer_md,
        ru_transfer_pdf,
        "Регламент управления внутренним перемещением товаров"
    )
    print()

    print("4. Генерация узбекской версии...")
    uz_transfer_md = os.path.join(docs_dir, "store-transfer-regulation-uz.md")
    uz_transfer_pdf = os.path.join(docs_dir, "store-transfer-regulation-uz.pdf")
    uz_transfer_success = markdown_to_pdf(
        uz_transfer_md,
        uz_transfer_pdf,
        "Mahsulotlarni ichki ko'chirishni boshqarish tartibi"
    )
    print()

    # Итоги
    print("=" * 60)
    all_success = (ru_writeoff_success and uz_writeoff_success and
                   ru_transfer_success and uz_transfer_success)

    if all_success:
        print("✓ Все PDF файлы успешно созданы!")
        print("\nСписание продуктов:")
        print(f"  - {os.path.basename(ru_writeoff_pdf)}")
        print(f"  - {os.path.basename(uz_writeoff_pdf)}")
        print("\nВнутреннее перемещение:")
        print(f"  - {os.path.basename(ru_transfer_pdf)}")
        print(f"  - {os.path.basename(uz_transfer_pdf)}")
        print()
        print(f"Файлы сохранены в: {docs_dir}")
    else:
        print("✗ Не все файлы были созданы. Проверьте ошибки выше.")
    print("=" * 60)

if __name__ == "__main__":
    main()
