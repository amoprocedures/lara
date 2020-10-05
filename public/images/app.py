import os


# Function to rename multiple files
def main():
    for count, filename in enumerate(os.listdir("products")):
        new_name = filename.replace('-', '_')
        print(filename)
        os.rename('products/' + filename, new_name)


# Driver Code


if __name__ == '__main__':
    # Calling main() function
    main()
