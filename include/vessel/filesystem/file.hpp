#ifndef FILE_HPP
#define FILE_HPP

#include <iostream>
#include <cmath>
#include <fstream>
#include <sstream>
#include <string>
#include <memory>
#include <boost/filesystem.hpp>
#include <boost/algorithm/string.hpp>
#include <cryptopp/sha.h>
#include <cryptopp/files.h>
#include <cryptopp/filters.h>
#include <cryptopp/hex.h>

#include <vessel/global.hpp>
#include <vessel/log/log.hpp>
#include <vessel/database/local_db.hpp>
#include <vessel/crypto/hash_util.hpp>
#include <vessel/filesystem/file_exception.hpp>

#define BACKUP_LARGE_SZ 52428800 //Default size in bytes of what should be considered a larger file (50MB)
#define BACKUP_CHUNK_SZ 52428800 //Default chunk size if not defined in DB (50MB)

using namespace Vessel::Logging;
using namespace Vessel::Database;
using namespace Vessel::Exception;
using namespace Vessel::Utilities;
using namespace CryptoPP;

namespace fs = boost::filesystem;

namespace Vessel {
    namespace File {

        class BackupFile
        {

            /*! \struct file_attrs
                \brief Struct containing common file attributes
            */
            struct file_attrs
            {
                std::string file_name;
                std::string file_type;
                std::string file_path;
                std::string parent_path;
                std::string relative_path;
                std::string canonical_path;
                std::string mime_type;
                std::string content_sha1;
                std::string content_sha256;
                size_t file_size;
                unsigned long last_write_time;
            };

            public:
                BackupFile(){}
                BackupFile(const fs::path& file_path); //Load file from filesystem
                BackupFile(std::shared_ptr<unsigned char> file_id); //Load file from database

                /*! \fn void set_path(const std::string& fp);
                    \brief Assigns a new file path to the object. Setting the path triggers all of the associated member attributes and variables to be updated with private method update_attributes()
                */
                void set_path(const std::string& fp);

                /*! \fn void set_path(const fs::path& file_path);
                    \brief Assigns a new file path to the object. Setting the path triggers all of the associated member attributes and variables to be updated with private method update_attributes()
                */
                void set_path(const fs::path& file_path);

                /*! \fn std::string get_file_name();
                    \brief
                    \return Returns the filename
                */
                std::string get_file_name() const;

                /*! \fn size_t get_file_size();
                    \brief
                    \return Returns the file size
                */
                size_t get_file_size() const;

                /*! \fn std::string get_file_type();
                    \return Returns the file extension as a string
                */
                std::string get_file_type() const;

                /*! \fn std::string get_mime_type();
                    \return Returns the MIME type for the file (if it exists)
                */
                std::string get_mime_type() const;

                /*! \fn static std::string get_mime_type(const std::string& file_type);
                    \brief Queries the local database by the file type to locate the MIME type
                    \param file_type The extension of the file (eg. ".jpg")
                    \return Returns the MIME type for the file extension (if it exists)
                */
                static std::string get_mime_type(const std::string& file_type);

                /*! \fn std::string get_file_contents();
                    \return Returns the file contents
                */
                std::string get_file_contents();

                /*! \fn std::string get_hash_sha1() const;
                    \brief
                    \return Returns a SHA-1 hash of the file contents
                */
                std::string get_hash_sha1();
                std::string get_hash_sha1() const;

                /*! \fn std::string get_hash_sha1(const std::string& content);
                    \brief
                    \return Returns a SHA-1 hash of the provided data
                */
                std::string get_hash_sha1(const std::string& data) const;

                /*! \fn std::string get_hash_sha256(const std::string& content);
                    \brief
                    \return Returns a SHA-256 hash of the provided data
                */
                std::string get_hash_sha256(const std::string& data) const;

                /*! \fn std::string get_hash_sha256();
                    \brief
                    \return Returns a SHA-256 hash of the file contents
                */
                std::string get_hash_sha256();

                /*! \fn unsigned int get_directory_id();
                    \brief
                    \return Returns the database ID of the directory
                */
                unsigned int get_directory_id() const;

                /*! \fn void set_directory_id(unsigned int id);
                    \brief Sets the associated database directory ID for the file (object only)
                */
                void set_directory_id(unsigned int id);

                /*! \fn std::shared_ptr<unsigned char> get_file_id();
                    \fn std::string get_file_id() const;
                    \brief
                    \return Returns the database unique ID of the file as a shared pointer
                */
                std::string get_file_id_text() const;
                std::shared_ptr<unsigned char> get_file_id() const;

                /*! \fn std::string get_file_path();
                    \brief
                    \return Returns the complete file path as a string
                */
                std::string get_file_path() const;

                /*! \fn std::string get_parent_path();
                    \brief
                    \return Returns the parent path of the file as a string
                */
                std::string get_parent_path() const;

                 /*! \fn std::string get_relative_path();
                    \brief
                    \return Returns the relative path of the file as a string
                */
                std::string get_relative_path() const;

                /*! \fn std::string get_canonical_path();
                    \brief
                    \return Returns the canonical (complete/full) path of the file as a string
                */
                std::string get_canonical_path() const;

                /*! \fn unsigned long get_last_modified();
                    \brief
                    \return Returns the last write time of the file as a Unix timestamp
                */
                unsigned long get_last_modified() const;

                /*! \fn bool exists();
                    \brief
                    \return Returns whether or not the file exists
                */
                bool exists();

                /*! \fn bool is_compressed();
                    \brief
                    \return Returns whether or not the file is compressed
                */
                //bool is_compressed();

                /*! \fn static void set_chunk_size( size_t chunk_sz );
                    \brief Sets the size of chunks of bytes returned from a file part for multi part uploads
                */
                static void set_chunk_size( size_t chunk_sz );

                 /*! \fn static size_t get_chunk_size();
                    \brief Returns the chunk size in bytes for multipart uploads
                */
                static size_t get_chunk_size();

                /*! \fn std::string get_file_part(unsigned int num);
                    \brief
                    \return Returns the bytes of a file for the given part number
                */
                std::string get_file_part(unsigned int num);

                /*! \fn std::string get_chunk(size_t offset, size_t length);
                    \brief
                    \return Returns a part of the file content at the specified offset and length
                */
                std::string get_chunk(size_t offset, size_t length);


                /*! \fn unsigned int get_total_parts();
                    \brief
                    \return Returns the total number of file parts based on the chunk size
                */
                unsigned int get_total_parts() const;

                /*! \fn void set_upload_id(unsigned int upload_id);
                    \brief Sets the database internal upload id for the file
                */
                void set_upload_id(unsigned int upload_id);

                /*! \fn void set_upload_key(const std::string& upload_key);
                    \brief Sets the server upload id/key for the file
                */
                void set_upload_key(const std::string& upload_key);

                /*! \fn unsigned int get_upload_id() const;
                    \brief
                    \return Returns the database upload id from the file
                */
                unsigned int get_upload_id() const;

                /*! \fn std::string get_upload_key() const;
                    \brief
                    \return Returns the server upload id/key from the file
                */
                std::string get_upload_key() const;

                /*! \fn std::shared_ptr<BackupFile> get_compressed_copy();
                    \brief Creates a compressed copy of the file stored in the tmp directory
                    \return Returns a BackupFile reference to the tmp compressed copy
                */
                //std::shared_ptr<BackupFile> get_compressed_copy();

                static std::string find_mime_type(const std::string& ext);

                /*! \fn bool is_readable();
                    \brief Returns whether or not the file can be opened for reading
                    \return Returns whether or not the file can be opened for reading
                */
                bool is_readable();

                /*! \fn update_last_backup();
                    \fn update_last_backup(const BackupFile& file);
                    \fn update_last_backup(std::shared_ptr<unsigned char> file_id);
                    \brief Sets the last backup time for the file to the current UNIX timestamp
                */
                void update_last_backup();
                static void update_last_backup(std::shared_ptr<unsigned char> file_id);

                /*! \fn static std::string strip_slashes(const std::string& str);
                    \brief Removes slashes from a file path
                    \return Removes slashes from a file path
                */
                static std::string trim_path(const std::string& str);

            private:
                boost::filesystem::path m_file_path; //!< Boost::FileSystem path of the file
                std::string m_file_id_s; //!< SHA-1 hash of the file path
                std::shared_ptr<unsigned char> m_file_id; //!< Raw SHA-1 hash of the file path
                std::string m_hash; //!< SHA-1 hash of the file contents
                std::string m_content; //!< Contents of the file (binary)
                file_attrs m_file_attrs; //!< Struct containing common file attributes
                unsigned int m_directory_id; //!< Database ID of the parent directory
                unsigned int m_upload_id; //!< Internal upload id for the file
                std::string m_upload_key; //!< Server Upload ID/Key of the file
                static size_t m_chunk_size; //!< Size in bytes in a file part for multi part uploads
                bool m_readable; //Can the file be opened for reading?

                /*! \fn void update_attributes()
                    \brief Updates private member variables for file properties. Called in constructor and when assigning an object a new file path
                */
                void update_attributes();

                /*! \fn std::string std::string calculate_unique_id();
                    \brief Calculates the SHA-1 hash of the file path
                */
                std::string calculate_unique_id() const;

        };

    }

}

#endif // FILE_HPP
