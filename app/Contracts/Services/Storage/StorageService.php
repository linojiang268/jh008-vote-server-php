<?php
namespace Jihe\Contracts\Services\Storage;

/**
 * interface for external storage service
 *
 * Stability: immature (interface can be changed dramatically)
 */
interface StorageService
{
    /**
     * store given file
     *
     * @param string $file      file path.   this file MUST exist
     * @param array $options    options
     *                          - id_only    when set to true(default), only identifier
     *                                       of the file will be returned.
     *                          - id         (optional) special identifier
     *                          - bucket     (optional) which bucket to store the file
     *                          - ext        (Optional) extension of the file. it's possible
     *                                       that $file does not have extension suffix.
     *                          - mime       (optional) mime of the file.
     *                          - prefix     (optional) prefix of object
     *
     * @return string|array     - if the returned value is a string, it's the identifier
     *                          of the file.
     *                          - if the returned value is an array, identifier of the
     *                          file should be included, which is keyed by 'id'.
     *
     * @throws \Exception       network/storage exception
     */
    public function store($file, array $options = []);
    
    /**
     * get file (identified by its identifier) from external storage
     *
     * @param string $identifier  identifier of the file
     * @param array $options      options
     *                            - content         when set to true(default), content
     *                                              of that file will be returned.
     *                            - metadata        when set to true, mime of that file
     *                                              will be returned. otherwise (false default),
     *                                              no mime returned.
     *                            - null_for_nonexistence
     *                                              when set to true(default), if the file
     *                                              does not exist, return null. otherwise,
     *                                              exception will be thrown.
     *                            - bucket          (optional) which bucket to find the file
     *
     * @return resource|array     - if both content and metadata is false, it will throw exception
     *                            - if only metadata is true, the returned value is a array of
     *                            the metadata.
     *                            - if only content is true, the returned value is a resource,
     *                            it's the content of the file. use stream_get_contents() to read it.
     *                            - if both content and metadata is true, the returned value
     *                            is an array, content of the file and array of metadata should
     *                            be included. which is keyed by 'content' and 'metadata'.
     */
    public function get($identifier, array $options = []);
    
    /**
     * remove the stored file(identified by its identifier)
     *
     * @param string $identifier     identifier of the file to be removed
     * @param array $options         options.
     *                               - inspect  when set to true(default false), no inspection
     *                                          will be performed to check the file's existence.
     *                               - bucket   (optional) which bucket to remove the file
     *
     * @return void    if there's no exception, the removal succeeded.
     */
    public function remove($identifier, array $options = []);

    /**
     * get objects from external storage
     *
     * @param string $prefix      prefix of object name(for example: tmp/ or tmp/20150926/)
     * @param array $options      options
     *                            - marker          return objects is sort order by letter, and
     *                                              after given marker, otherwise from the
     *                                              top if marker is not given, likes: (
     *                                              "a" or "a/" or "a/b/")
     *                            - max_keys        max of how many objects will return, 100(default)
     *                            - bucket          (optional) which bucket to find objects
     *
     * @return array              array of objects, objects keys taken:
     *                             - name           object name, likes: "a"
     *                             - original_name  original name of object, likes: ("a" or "a/"
     *                                              or "a/b.jpg" or "a/b/" or "a/b/c.jpg")
     *                             - folder         (boolean)whether object is folder
     *                             - size           the size of object if object is file, otherwise
     *                                              0 given if object is folder
     *                             - last_modified  the time that last modified as "1990-11-11 11:11:11"
     */
    public function listObjects($prefix = null, array $options = []);

    /**
     * store by copy the given file already uploaded
     *
     * @param string $source    identifier of source
     * @param array $options    options
     *                          - id_only         when set to true(default), only identifier
     *                                            of the file will be returned.
     *                          - source_bucket   (optional) which bucket the source file is stored
     *                          - dest_bucket     (optional) which bucket to store the file
     *                          - id              (optional) special identifier
     *                          - ext             (Optional) extension of the file. it's possible
     *                                            that $file does not have extension suffix.
     *                          - mime            (optional) mime of the file.
     *                          - prefix          (optional) prefix of object
     *
     * @return string|array     - if the returned value is a string, it's the identifier
     *                            of the file.
     *                          - if the returned value is an array, identifier of the
     *                            file should be included, which is keyed by 'id'.
     *
     * @throws \Exception       network/storage exception
     */
    public function copy($source, array $options = []);
}
