from pptx import Presentation
from pptx.util import Inches, Pt
from pptx.enum.shapes import MSO_SHAPE
from pptx.dml.color import RGBColor
import os
from zipfile import ZipFile

# Create presentation object
prs = Presentation()

# Define slide layout
title_slide_layout = prs.slide_layouts[0]
blank_slide_layout = prs.slide_layouts[6]

# Title Slide
slide = prs.slides.add_slide(title_slide_layout)
title = slide.shapes.title
subtitle = slide.placeholders[1]
title.text = "Pamilihan Quiz Showdown!"
subtitle.text = "An Interactive Game for Araling Panlipunan 9"

# Instructions Slide
slide = prs.slides.add_slide(blank_slide_layout)
shapes = slide.shapes
title_box = shapes.add_textbox(Inches(0.5), Inches(0.3), Inches(9), Inches(1))
frame = title_box.text_frame
frame.text = "🎮 Game Instructions"
p = frame.add_paragraph()
p.text = "- 3 rounds: Multiple Choice, Identification, Essay"
p = frame.add_paragraph()
p.text = "- Click on your answer to proceed."
p = frame.add_paragraph()
p.text = "- No going back once answered."
p = frame.add_paragraph()
p.text = "- Essay round is for discussion or written output."

# Sample Question Slide (from Multiple Choice)
mc_questions = [
    ("Ano ang pangunahing papel ng pamilihan sa ekonomiya?", ["A. Magbigay ng trabaho sa mga tao", "B. Magkontrol sa presyo ng mga produkto", "C. Magpalitan ng mga produkto at serbisyo", "D. Mag-ambag sa Lipunan"], "C"),
]

# Add Multiple Choice Question Slide
for q_text, choices, correct in mc_questions:
    slide = prs.slides.add_slide(blank_slide_layout)
    shapes = slide.shapes
    q_box = shapes.add_textbox(Inches(0.5), Inches(0.3), Inches(9), Inches(1))
    q_frame = q_box.text_frame
    q_frame.text = "❓ " + q_text

    # Add options
    for i, choice in enumerate(choices):
        y = 1.5 + i * 0.8
        btn = shapes.add_shape(MSO_SHAPE.ROUNDED_RECTANGLE, Inches(1), Inches(y), Inches(7), Inches(0.6))
        btn.text = choice
        fill = btn.fill
        fill.solid()
        if choice.startswith(correct):
            fill.fore_color.rgb = RGBColor(0, 176, 80)  # green
        else:
            fill.fore_color.rgb = RGBColor(192, 0, 0)  # red
        text_frame = btn.text_frame
        text_frame.paragraphs[0].font.size = Pt(18)

# Essay Slide
essay_qs = [
    "1. Sa iyong palagay, bakit mahalaga ang pakikialam at regulasyon ng pamahalaan sa mga negosyo at pamilihan?",
    "2. Bilang isang mag-aaral, paano mo ipaliliwanag ang pagkakaiba ng mga uri ng istruktura ng pamilihan kagaya ng kompetisyon, monopoly at oligopolyo?"
]
for essay in essay_qs:
    slide = prs.slides.add_slide(blank_slide_layout)
    shapes = slide.shapes
    tbox = shapes.add_textbox(Inches(0.5), Inches(0.5), Inches(9), Inches(6))
    tf = tbox.text_frame
    tf.text = "📝 " + essay

# Save PPT file
pptx_path = "/mnt/data/Pamilihan_Game_Showdown.pptx"
prs.save(pptx_path)

# Zip the file
zip_path = "/mnt/data/Pamilihan_Game_Showdown.zip"
with ZipFile(zip_path, 'w') as zipf:
    zipf.write(pptx_path, os.path.basename(pptx_path))

zip_path
