import fitz
from PIL import Image
import os
from pdf2image import convert_from_path
import shutil
import json

class Page:
    def __init__(self, number: int, text: str, images: list):
        self.number = number
        self.text = text
        self.images = images

    def __str__(self):
        return f"Page {self.number}: {self.text}"

    def to_dict(self):
        return {
            "number": self.number,
            "text": self.text.decode("utf8"),
            "images": [image.to_dict() for image in self.images]
        }

class Image:
    def __init__(self, page: int, number: int, path: str, x: float, y: float, width: float, height: float):
        self.page = page
        self.number = number
        self.path = path
        self.x = x
        self.y = y
        self.width = width
        self.height = height

    def bbox(self):
        return fitz.Rect(self.x, self.y, self.x + self.width, self.y + self.height)

    def text_bbox(self):
        return fitz.Rect(self.x, self.y, self.x + 100, self.y + 70)

    def to_dict(self):
        return {
            "page": self.page,
            "number": self.number,
            "path": self.path,
            "x": self.x,
            "y": self.y,
            "width": self.width,
            "height": self.height
        }

def draw_red_borders_around_images(doc, images):
    for page in doc:
        img_list = page.get_images(full=True)

        # every image where page is page.number
        page_images = [image for image in images if image.page == page.number]

        for image in page_images:
            page.draw_rect(image.bbox(), color=(1, 0, 0), width=10)

            # draw "IMAGE" in the middle of the image
            page.draw_rect(image.text_bbox(), color=(1,0,0), fill=(1, 0, 0))

            shape = page.new_shape()
            shape.insert_textbox(image.text_bbox(), str(image.number), fontsize=40, color=(1, 1, 1), align=1)
            shape.finish(fill=(1, 0, 0))
            shape.commit()

def save_image(doc, image, path):
    xref = image[0]
    # extract the image bytes
    base_image = doc.extract_image(xref)
    image_bytes = base_image["image"]

    # Check that the bytes are at least 200 bytes long, otherwise it's probably a corrupted image.
    # This is based on experience and can be adjusted/improved with proper corruption detection.
    # But for now, this is a simple and effective way to filter out corrupted images.
    if len(image_bytes) < 200:
        return False

    # you can save the image to disk with open()
    with open(path, "wb") as img_file:
        img_file.write(image_bytes)

    return True

def save_pages_as_files(doc, artifact_dir, pages_dir, images_dir = None, full_text_path = None, contents_path = None, pages_txt_dir = None):
    pages = []
    texts = []
    contents = []
    page_images = []
    images = []

    for (page, pageIndex) in zip(doc, range(len(doc))):
        page_images = []
        page_image_path = pages_dir + f"/page{pageIndex + 1}.jpg"

        text = page.get_text().encode("utf8")
        texts.append(text)
        contents.append({"page": pageIndex + 1, "type": "text", "text": text.decode("utf8")})

        if pages_txt_dir is not None:
            text_path = pages_txt_dir + f"/page{pageIndex + 1}.txt"
            with open(text_path, "wb") as text_file:
                text_file.write(text)

        page = doc.load_page(pageIndex)
        pix = page.get_pixmap()
        pix.save(page_image_path)

        page_image = Image(page.number, 0, page_image_path, 0, 0, pix.width, pix.height)
        page_images.append(page_image)

        contents.append({"page": pageIndex + 1, "type": "page-image-marked", "mimetype": "image/jpeg", "path": "pages_marked/page" + str(pageIndex + 1) + ".jpg"})
        contents.append({"page": pageIndex + 1, "type": "page-image", "mimetype": "image/jpeg", "path": "pages/page" + str(pageIndex + 1) + ".jpg"})

        if images_dir is not None:
            img_list = page.get_images(full=True)

            for image in img_list:
                bbox = page.get_image_bbox(image)
                number = len(images) + 1

                image_path = images_dir + f"/image{number}.jpg"
                success = save_image(doc, image, image_path)

                # if image is not saved, skip.
                # this can happen for corrupted images
                if not success:
                    continue

                relative_image_path = os.path.relpath(image_path, artifact_dir)
                image = Image(page.number, number, relative_image_path, bbox.x0, bbox.y0, bbox.width, bbox.height)

                if image.width > 200 or image.height > 200:
                    page_images.append(image)
                    images.append(image)
                    contents.append({"page": pageIndex + 1, "type": "image", "mimetype": "image/jpeg", "path": image.path, "x": image.x, "y": image.y, "width": image.width, "height": image.height})

        pages.append(Page(pageIndex + 1, text, page_images))

    # join texts with "---- Page X ----" header for each page with number of page replaced
    if full_text_path is not None:
        full_text = b""
        pageIndex = 0

        for text in texts:
            pageIndex += 1
            full_text += f"---- Page {pageIndex} ----\n".encode("utf8")
            full_text += text
            full_text += b"\n\n"

        with open(full_text_path, "wb") as text_file:
            text_file.write(full_text)

    if contents_path is not None:
        with open(contents_path, "w") as f:
            f.write(json.dumps(contents, indent=4))


    return images

# def resize_pages():
#     pages_path = artifact_dir + f"/pages_marked/"
#
#     for page in os.listdir(pages_path):
#         image = Image.open(pages_path + page)
#         image = image.resize((int(image.width / 2), int(image.height / 2)))
#         image.save(pages_path + page)

def extract_pages(doc, images_dir):
    return []
